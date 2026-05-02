<?php
/**
 * Endpoint AJAX — Synchronisation forcée d'un produit vers une marketplace
 *
 * Appelé en POST via fetch() depuis product_tab.php
 * Retourne du JSON
 */

// Ces constantes DOIVENT être définies AVANT main.inc.php
define('NOTOKENRENEWAL', 1);
define('NOREQUIRETOKENRENEWAL', 1);

@ini_set('display_errors', 0);
@ini_set('display_startup_errors', 0);
error_reporting(0);

// Charger Dolibarr
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && file_exists("../../main.inc.php"))  { $res = @include "../../main.inc.php"; }
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php"; }

// Réponse toujours en JSON
header('Content-Type: application/json; charset=utf-8');

if (!$res) {
    echo json_encode(array('ok' => false, 'msg' => 'Erreur chargement Dolibarr'));
    exit;
}

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// ── Sécurité ──────────────────────────────────────────────────────────────────
$can_sync = $user->admin || (!empty($user->rights->marketplace_bdc->marketplace->sync));
if (!$can_sync) {
    echo json_encode(array('ok' => false, 'msg' => 'Permission refusée (sync)'));
    exit;
}

$product_id = (int) GETPOST('product_id', 'int');
$mkt_target = GETPOST('mkt_id', 'alphanohtml');

if (!$product_id) {
    echo json_encode(array('ok' => false, 'msg' => 'product_id manquant'));
    exit;
}

// ── Chargement produit ────────────────────────────────────────────────────────
$product = new Product($db);
if ($product->fetch($product_id) <= 0) {
    echo json_encode(array('ok' => false, 'msg' => 'Produit introuvable (id='.$product_id.')'));
    exit;
}
$product->load_stock();
$product->fetch_optionals();
$stock_reel = (float) $product->stock_reel;

// ── Images produit ────────────────────────────────────────────────────────────
$product_images = array();
$img_dir = (isset($conf->product->dir_output) ? $conf->product->dir_output : DOL_DATA_ROOT.'/produit')
         . '/' . dol_sanitizeFileName($product->ref);
if (is_dir($img_dir)) {
    $files = @scandir($img_dir);
    if ($files) {
        foreach ($files as $f) {
            if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f) && strpos($f, 'thumbs') === false) {
                $product_images[] = DOL_MAIN_URL_ROOT
                    . '/viewimage.php?modulepart=product&entity='.(int)$conf->entity
                    . '&file='.urlencode(dol_sanitizeFileName($product->ref).'/'.$f);
            }
        }
    }
}

// ── Chargement configs ────────────────────────────────────────────────────────
$all_mkt_raw = isset($conf->global->MARKETPLACE_BDC_MARKETPLACES) ? $conf->global->MARKETPLACE_BDC_MARKETPLACES : '{}';
$all_mkt     = json_decode($all_mkt_raw, true);
if (!is_array($all_mkt)) { $all_mkt = array(); }

$prod_key    = 'MARKETPLACE_BDC_PRODUCT_'.$product_id;
$prod_raw    = isset($conf->global->$prod_key) ? $conf->global->$prod_key : '{}';
$prod_config = json_decode($prod_raw, true);
if (!is_array($prod_config)) { $prod_config = array(); }

// ── Sélection des marketplaces à synchroniser ─────────────────────────────────
$targets = array();
foreach ($all_mkt as $mkt_id => $mkt_info) {
    $is_enabled = !empty($mkt_info['enabled']);
    $is_synced  = !empty($prod_config[$mkt_id]['synced']);
    $is_target  = ($mkt_target === 'all' || $mkt_target === $mkt_id);

    if ($is_enabled && $is_target) {
        // Si 'all' on ne prend que ceux activés sur le produit, si marketplace spécifique on force
        if ($mkt_target === 'all' && !$is_synced) { continue; }
        $targets[$mkt_id] = $mkt_info;
    }
}

if (empty($targets)) {
    $msg = $mkt_target === 'all'
        ? 'Aucune marketplace active avec sync activée sur ce produit. Activez la synchronisation dans la configuration produit.'
        : 'La marketplace "'.$mkt_target.'" n\'est pas activée ou introuvable.';
    echo json_encode(array('ok' => false, 'msg' => $msg));
    exit;
}

// ── Résultats agrégés ─────────────────────────────────────────────────────────
$results          = array();
$now              = date('Y-m-d H:i:s');
$prod_cfg_updated = $prod_config;

foreach ($targets as $mkt_id => $mkt_info) {
    $pcfg     = $prod_config[$mkt_id] ?? array();
    $mkt_maps = $mkt_info['mappings'] ?? array();

    // Calcul prix ajusté
    $base_price  = (float) $product->price_ttc;
    $adj_type    = $pcfg['adj_type'] ?? 'none';
    $adj_val     = (float) ($pcfg['adj_val'] ?? 0);
    switch ($adj_type) {
        case 'add_pct': $final_price = $base_price * (1 + $adj_val / 100); break;
        case 'sub_pct': $final_price = $base_price * (1 - $adj_val / 100); break;
        case 'add_eur': $final_price = $base_price + $adj_val; break;
        case 'sub_eur': $final_price = $base_price - $adj_val; break;
        default:        $final_price = $base_price;
    }
    $final_price = max(0, round($final_price, 2));

    // Calcul stock ajusté
    $stock_buf   = (int) ($pcfg['stock_buf'] ?? 0);
    $final_stock = max(0, (int) $stock_reel - $stock_buf);

    // Payload avec les champs Dolibarr disponibles
    $dolibarr_fields = array(
        // Identification
        'ref'             => $product->ref,
        'ref_ext'         => $product->ref_ext,
        'barcode'         => $product->barcode,
        'label'           => $product->label,
        // Description
        'description'     => strip_tags($product->description),
        'note_public'     => strip_tags($product->note),
        'url'             => $product->url,
        // Prix
        'price_ttc'       => $final_price,
        'price'           => $product->price,
        'price_min'       => $product->price_min,
        'price_min_ttc'   => $product->price_min_ttc,
        'cost_price'      => $product->cost_price,
        'tva_tx'          => $product->tva_tx,
        // Stock / logistique
        'stock_reel'      => $final_stock,
        'desiredstock'    => $product->desiredstock,
        'weight'          => $product->weight,
        'length'          => $product->length,
        'width'           => $product->width,
        'height'          => $product->height,
        'surface'         => $product->surface,
        'volume'          => $product->volume,
        // Classification
        'customcode'      => $product->customcode,
        'finished'        => $product->finished,
        'packaging'       => $product->packaging,
        // Images
        'image_main_url'  => !empty($product_images) ? $product_images[0] : '',
        'image_url_1'     => $product_images[0] ?? '',
        'image_url_2'     => $product_images[1] ?? '',
        'image_url_3'     => $product_images[2] ?? '',
        'image_url_4'     => $product_images[3] ?? '',
        'image_url_5'     => $product_images[4] ?? '',
        'images_count'    => count($product_images),
    );

    // Champs personnalisés (extrafields)
    if (!empty($product->array_options)) {
        foreach ($product->array_options as $opt_key => $opt_val) {
            // $opt_key est déjà de la forme 'options_xxx'
            $dolibarr_fields[$opt_key] = $opt_val;
        }
    }

    // Construction du payload via mappings
    $sync_desc   = !isset($pcfg['sync_desc'])   || !empty($pcfg['sync_desc']);
    $sync_price  = !isset($pcfg['sync_price'])  || !empty($pcfg['sync_price']);
    $sync_stock  = !isset($pcfg['sync_stock'])  || !empty($pcfg['sync_stock']);
    $sync_images = !isset($pcfg['sync_images']) || !empty($pcfg['sync_images']);

    $payload = array('_source_ref' => $product->ref, '_source_id' => $product_id);
    foreach (array('product', 'price', 'stock') as $flow) {
        if (!empty($mkt_maps[$flow])) {
            foreach ($mkt_maps[$flow] as $mapping) {
                $src = $mapping['source'] ?? '';
                $tgt = $mapping['target'] ?? '';
                if (!$src || !$tgt) { continue; }
                if (!array_key_exists($src, $dolibarr_fields)) { continue; }

                // Respect des flags de sync
                if ($flow === 'price' && !$sync_price) { continue; }
                if ($flow === 'stock' && !$sync_stock) { continue; }
                if ($flow === 'product') {
                    if (!$sync_desc && in_array($src, array('description', 'note_public', 'label', 'url'))) { continue; }
                    if (!$sync_images && strpos($src, 'image') === 0) { continue; }
                }

                $payload[$tgt] = $dolibarr_fields[$src];
            }
        }
    }

    // Appel API marketplace
    $sync_result = mkt_call_api($mkt_id, $mkt_info, $payload, $product->ref);

    // Mise à jour config produit
    if (!isset($prod_cfg_updated[$mkt_id])) { $prod_cfg_updated[$mkt_id] = array(); }
    $prod_cfg_updated[$mkt_id]['last_sync']   = $now;
    $prod_cfg_updated[$mkt_id]['last_status'] = $sync_result['ok'] ? 'ok' : $sync_result['msg'];
    $prod_cfg_updated[$mkt_id]['last_sku']    = $product->ref;

    // Conserver les flags de config produit
    foreach (array('synced','sync_desc','sync_price','sync_stock','adj_type','adj_val','stock_buf') as $k) {
        if (isset($prod_config[$mkt_id][$k])) {
            $prod_cfg_updated[$mkt_id][$k] = $prod_config[$mkt_id][$k];
        }
    }

    $results[$mkt_id] = array(
        'name'    => $mkt_info['name'] ?? $mkt_id,
        'ok'      => $sync_result['ok'],
        'msg'     => $sync_result['msg'],
        'price'   => $final_price,
        'stock'   => $final_stock,
    );
}

// Sauvegarde config produit
dolibarr_set_const($db, $prod_key, json_encode($prod_cfg_updated), 'chaine', 0, '', $conf->entity);

echo json_encode(array(
    'ok'      => true,
    'results' => $results,
    'at'      => $now,
));
exit;

// ════════════════════════════════════════════════════════════════════════════
// FONCTIONS D'APPEL API PAR MARKETPLACE
// ════════════════════════════════════════════════════════════════════════════

function mkt_call_api($mkt_id, $mkt_info, $payload, $sku)
{
    $api_url = $mkt_info['endpoints']['api'] ?? '';
    if (!$api_url) {
        return array('ok' => false, 'msg' => 'Endpoint API non configuré pour cette marketplace');
    }

    // Routing par marketplace
    if ($mkt_id === 'amazon') {
        return mkt_sync_amazon($mkt_info, $payload, $sku);
    } elseif ($mkt_id === 'cdiscount') {
        return mkt_sync_cdiscount($mkt_info, $payload, $sku);
    } elseif (strpos($mkt_id, 'mirakl') === 0) {
        return mkt_sync_mirakl($mkt_info, $payload, $sku);
    } else {
        return mkt_sync_generic($mkt_info, $payload, $sku);
    }
}

// ── Amazon SP-API (OAuth2 LWA) ────────────────────────────────────────────────
function mkt_sync_amazon($mkt_info, $payload, $sku)
{
    $client_id     = $mkt_info['client_id']     ?? '';
    $client_secret = $mkt_info['client_secret'] ?? '';
    $refresh_token = $mkt_info['refresh_token'] ?? '';
    $auth_url      = $mkt_info['endpoints']['auth'] ?? 'https://api.amazon.com/auth/o2/token';
    $api_url       = $mkt_info['endpoints']['api']  ?? 'https://sellingpartnerapi-eu.amazon.com';
    $marketplace_id= $mkt_info['marketplace_id'] ?? 'A13V1IB3VIYZZH';
    $seller_id     = $mkt_info['seller_id'] ?? '';

    if (!$client_id || !$client_secret || !$refresh_token) {
        return array('ok' => false, 'msg' => 'Credentials Amazon incomplets (client_id, client_secret, refresh_token requis)');
    }
    if (!$seller_id) {
        return array('ok' => false, 'msg' => 'Seller ID Amazon manquant');
    }

    // 1. LWA Token
    $ch = curl_init($auth_url);
    curl_setopt_array($ch, array(
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(array(
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
        )),
        CURLOPT_HTTPHEADER     => array('Content-Type: application/x-www-form-urlencoded'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $auth_resp = curl_exec($ch);
    $auth_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $auth_err  = curl_error($ch);
    curl_close($ch);

    if ($auth_err) {
        return array('ok' => false, 'msg' => 'Auth Amazon - erreur réseau: '.$auth_err);
    }
    if ($auth_code >= 400) {
        $auth_data = json_decode($auth_resp, true);
        $err_msg = isset($auth_data['error_description']) ? $auth_data['error_description'] : substr($auth_resp, 0, 200);
        return array('ok' => false, 'msg' => 'Auth Amazon échouée (HTTP '.$auth_code.'): '.$err_msg);
    }

    $auth_data    = json_decode($auth_resp, true);
    $access_token = $auth_data['access_token'] ?? '';
    if (!$access_token) {
        return array('ok' => false, 'msg' => 'Access token Amazon non reçu: '.substr($auth_resp, 0, 200));
    }

    // 2. Patch listing SP-API v2021-08-01
    // Construire les attributs en format SP-API
    $attributes = array(
        'condition_type' => array(array('value' => 'new_new', 'marketplace_id' => $marketplace_id)),
    );
    // Clés spéciales à ne pas transformer
    $skip_keys = array('_source_ref', '_source_id');
    foreach ($payload as $k => $v) {
        if (in_array($k, $skip_keys)) { continue; }
        $attributes[$k] = array(array('value' => $v, 'marketplace_id' => $marketplace_id));
    }

    $listing_body = array(
        'productType' => 'PRODUCT',
        'patches'     => array(
            array(
                'op'    => 'replace',
                'path'  => '/attributes',
                'value' => $attributes,
            )
        ),
    );

    $url = $api_url.'/listings/2021-08-01/items/'.urlencode($seller_id).'/'.urlencode($sku)
         . '?marketplaceIds='.$marketplace_id.'&issueLocale=fr_FR';

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_POSTFIELDS     => json_encode($listing_body),
        CURLOPT_HTTPHEADER     => array(
            'Content-Type: application/json',
            'Accept: application/json',
            'x-amz-access-token: '.$access_token,
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)       { return array('ok' => false, 'msg' => 'Amazon API - erreur réseau: '.$err); }

    $resp_data = json_decode($resp, true);
    if ($code >= 400) {
        $err_detail = '';
        if (isset($resp_data['errors'][0]['message'])) {
            $err_detail = $resp_data['errors'][0]['message'];
        } else {
            $err_detail = substr($resp, 0, 300);
        }
        return array('ok' => false, 'msg' => 'Amazon SP-API HTTP '.$code.': '.$err_detail);
    }

    $status = $resp_data['status'] ?? 'ACCEPTED';
    return array('ok' => true, 'msg' => 'Amazon OK — statut: '.$status.' (HTTP '.$code.')');
}

// ── Cdiscount (OAuth2) ────────────────────────────────────────────────────────
function mkt_sync_cdiscount($mkt_info, $payload, $sku)
{
    $client_id     = $mkt_info['client_id']     ?? '';
    $client_secret = $mkt_info['client_secret'] ?? '';
    $auth_url      = $mkt_info['endpoints']['auth'] ?? 'https://api.cdiscount.com/api/1.0/auth/GenerateToken';
    $api_url       = $mkt_info['endpoints']['api']  ?? 'https://api.cdiscount.com/api/1.0';

    if (!$client_id || !$client_secret) {
        return array('ok' => false, 'msg' => 'Credentials Cdiscount (client_id, client_secret) manquants');
    }

    $ch = curl_init($auth_url);
    curl_setopt_array($ch, array(
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(array('ClientID' => $client_id, 'ClientSecret' => $client_secret)),
        CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $auth_resp = curl_exec($ch);
    $auth_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $auth_err  = curl_error($ch);
    curl_close($ch);

    if ($auth_err || $auth_code >= 400) {
        return array('ok' => false, 'msg' => 'Auth Cdiscount échouée (HTTP '.$auth_code.') '.$auth_err);
    }

    $auth_data = json_decode($auth_resp, true);
    $token     = $auth_data['TokenId'] ?? ($auth_data['access_token'] ?? '');
    if (!$token) {
        return array('ok' => false, 'msg' => 'Token Cdiscount non reçu: '.substr($auth_resp, 0, 200));
    }

    $skip_keys    = array('_source_ref', '_source_id');
    $product_data = array('SellerProductId' => $sku);
    foreach ($payload as $k => $v) {
        if (!in_array($k, $skip_keys)) { $product_data[$k] = $v; }
    }

    $ch = curl_init($api_url.'/products');
    curl_setopt_array($ch, array(
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(array('Products' => array($product_data))),
        CURLOPT_HTTPHEADER     => array('Content-Type: application/json', 'Authorization: Bearer '.$token),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)       { return array('ok' => false, 'msg' => 'cURL Cdiscount: '.$err); }
    if ($code >= 400) { return array('ok' => false, 'msg' => 'Cdiscount HTTP '.$code.' — '.substr($resp, 0, 200)); }

    return array('ok' => true, 'msg' => 'Cdiscount OK (HTTP '.$code.')');
}

// ── Mirakl (API Key — toutes plateformes mirakl_*) ───────────────────────────
function mkt_sync_mirakl($mkt_info, $payload, $sku)
{
    $api_key = $mkt_info['api_key'] ?? '';
    $api_url = $mkt_info['endpoints']['api'] ?? '';

    if (!$api_key) {
        return array('ok' => false, 'msg' => 'API Key Mirakl manquante');
    }

    $skip_keys  = array('_source_ref', '_source_id');
    $offer_data = array(
        'sku'               => $sku,
        'product_sku'       => $sku,
        'state_code'        => '11',
        'currency_iso_code' => 'EUR',
    );
    foreach ($payload as $k => $v) {
        if (!in_array($k, $skip_keys)) { $offer_data[$k] = $v; }
    }

    $ch = curl_init($api_url.'/offers');
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode(array('offers' => array($offer_data))),
        CURLOPT_HTTPHEADER     => array(
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: '.$api_key,
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)       { return array('ok' => false, 'msg' => 'cURL Mirakl: '.$err); }
    if ($code >= 400) { return array('ok' => false, 'msg' => 'Mirakl HTTP '.$code.' — '.substr($resp, 0, 200)); }

    return array('ok' => true, 'msg' => 'Mirakl OK (HTTP '.$code.')');
}

// ── Marketplace générique (REST JSON PUT) ─────────────────────────────────────
function mkt_sync_generic($mkt_info, $payload, $sku)
{
    $api_url   = $mkt_info['endpoints']['api'] ?? '';
    $api_key   = $mkt_info['api_key']   ?? '';
    $auth_type = $mkt_info['auth_type'] ?? 'apikey';

    $skip_keys = array('_source_ref', '_source_id');
    $body      = array();
    foreach ($payload as $k => $v) {
        if (!in_array($k, $skip_keys)) { $body[$k] = $v; }
    }

    $headers = array('Content-Type: application/json', 'Accept: application/json');
    if ($auth_type === 'apikey' && $api_key) { $headers[] = 'Authorization: '.$api_key; }
    if ($auth_type === 'oauth2' && !empty($mkt_info['client_id'])) {
        $headers[] = 'Authorization: Bearer '.$mkt_info['client_id'];
    }

    $ch = curl_init($api_url.'/products/'.urlencode($sku));
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)       { return array('ok' => false, 'msg' => 'cURL: '.$err); }
    if ($code >= 400) { return array('ok' => false, 'msg' => 'HTTP '.$code.' — '.substr($resp, 0, 200)); }

    return array('ok' => true, 'msg' => 'OK (HTTP '.$code.')');
}
