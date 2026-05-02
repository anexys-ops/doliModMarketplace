<?php
/**
 * Endpoint AJAX — Synchronisation forcée d'un produit vers une marketplace
 *
 * Appelé en POST via fetch() depuis product_tab.php
 * Retourne du JSON
 */

// Désactiver la sortie HTML d'erreur pour JSON propre
@ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

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
if (!$res && file_exists("../../main.inc.php")) { $res = @include "../../main.inc.php"; }
if (!$res && file_exists("../../../main.inc.php")) { $res = @include "../../../main.inc.php"; }
if (!$res) { echo json_encode(array('ok' => false, 'msg' => 'Erreur chargement Dolibarr')); exit; }

define('NOTOKENRENEWAL', 1); // Page AJAX — pas de renouvellement de token

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/product.class.php';

// ── Sécurité ──────────────────────────────────────────────────────────────────
if (!$user->hasRight('marketplace_bdc', 'marketplace', 'sync')) {
    echo json_encode(array('ok' => false, 'msg' => 'Permission refusée')); exit;
}

$product_id = (int) GETPOST('product_id', 'int');
$mkt_target = GETPOST('mkt_id', 'alpha');   // 'all' ou un id de marketplace

if (!$product_id) {
    echo json_encode(array('ok' => false, 'msg' => 'product_id manquant')); exit;
}

// ── Chargement produit ────────────────────────────────────────────────────────
$product = new Product($db);
if ($product->fetch($product_id) <= 0) {
    echo json_encode(array('ok' => false, 'msg' => 'Produit introuvable')); exit;
}

// Stock réel
$product->load_stock();
$stock_reel = (float) $product->stock_reel;

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
    if (!empty($mkt_info['enabled'])
        && isset($prod_config[$mkt_id]['synced']) && $prod_config[$mkt_id]['synced']
        && ($mkt_target === 'all' || $mkt_target === $mkt_id)) {
        $targets[$mkt_id] = $mkt_info;
    }
}

if (empty($targets)) {
    echo json_encode(array('ok' => false, 'msg' => 'Aucune marketplace active/sélectionnée pour ce produit')); exit;
}

// ── Résultats agrégés ─────────────────────────────────────────────────────────
$results  = array();
$now      = date('Y-m-d H:i:s');
$prod_cfg_updated = $prod_config;

foreach ($targets as $mkt_id => $mkt_info) {
    $pcfg     = $prod_config[$mkt_id] ?? array();
    $mkt_maps = isset($mkt_info['mappings']) ? $mkt_info['mappings'] : array();

    // ── Calcul prix ajusté ────────────────────────────────────────────────────
    $base_price = (float) $product->price_ttc;
    $adj_type   = $pcfg['adj_type'] ?? 'none';
    $adj_val    = (float) ($pcfg['adj_val'] ?? 0);
    switch ($adj_type) {
        case 'add_pct': $final_price = $base_price * (1 + $adj_val / 100); break;
        case 'sub_pct': $final_price = $base_price * (1 - $adj_val / 100); break;
        case 'add_eur': $final_price = $base_price + $adj_val; break;
        case 'sub_eur': $final_price = $base_price - $adj_val; break;
        default:        $final_price = $base_price;
    }
    $final_price = max(0, round($final_price, 2));

    // ── Calcul stock ajusté ───────────────────────────────────────────────────
    $stock_buf   = (int) ($pcfg['stock_buf'] ?? 0);
    $final_stock = max(0, (int) $stock_reel - $stock_buf);

    // ── Payload produit (Dolibarr → marketplace via mappings) ─────────────────
    $dolibarr_fields = array(
        'ref'         => $product->ref,
        'label'       => $product->label,
        'description' => strip_tags($product->description),
        'barcode'     => $product->barcode,
        'weight'      => $product->weight,
        'price_ttc'   => $final_price,
        'price'       => $product->price,
        'stock_reel'  => $final_stock,
        'tva_tx'      => $product->tva_tx,
    );

    // Construction du payload avec les mappings configurés
    $payload = array('_source_ref' => $product->ref);
    foreach (array('product', 'price', 'stock') as $flow) {
        if (!empty($mkt_maps[$flow])) {
            foreach ($mkt_maps[$flow] as $mapping) {
                $src = $mapping['source'] ?? '';
                $tgt = $mapping['target'] ?? '';
                if ($src && $tgt && isset($dolibarr_fields[$src])) {
                    // Respecter les flags de sync
                    if ($flow === 'price'   && empty($pcfg['sync_price']))  { continue; }
                    if ($flow === 'stock'   && empty($pcfg['sync_stock']))  { continue; }
                    if ($flow === 'product' && empty($pcfg['sync_desc']))   {
                        // On garde ref et barcode même sans desc
                        if (in_array($src, array('description', 'label'))) { continue; }
                    }
                    $payload[$tgt] = $dolibarr_fields[$src];
                }
            }
        }
    }

    // ── Appel API marketplace ─────────────────────────────────────────────────
    $sync_result = mkt_call_api($mkt_id, $mkt_info, $payload, $product->ref);

    // ── Mise à jour config produit ────────────────────────────────────────────
    $prod_cfg_updated[$mkt_id]['last_sync']   = $now;
    $prod_cfg_updated[$mkt_id]['last_status'] = $sync_result['ok'] ? 'ok' : $sync_result['msg'];
    $prod_cfg_updated[$mkt_id]['last_sku']    = $product->ref;

    $results[$mkt_id] = array(
        'name'    => $mkt_info['name'] ?? $mkt_id,
        'ok'      => $sync_result['ok'],
        'msg'     => $sync_result['msg'],
        'payload' => $payload,
        'price'   => $final_price,
        'stock'   => $final_stock,
    );
}

// ── Sauvegarde config produit mise à jour ─────────────────────────────────────
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
    $auth_type = $mkt_info['auth_type'] ?? 'apikey';
    $api_url   = $mkt_info['endpoints']['api'] ?? '';

    if (!$api_url) {
        return array('ok' => false, 'msg' => 'Endpoint API non configuré');
    }

    switch ($mkt_id) {
        case 'cdiscount':   return mkt_sync_cdiscount($mkt_info, $payload, $sku);
        case 'mirakl_adeo': return mkt_sync_mirakl($mkt_info, $payload, $sku);
        case 'amazon':      return mkt_sync_amazon($mkt_info, $payload, $sku);
        default:            return mkt_sync_generic($mkt_info, $payload, $sku);
    }
}

// ── Cdiscount (OAuth2) ────────────────────────────────────────────────────────
function mkt_sync_cdiscount($mkt_info, $payload, $sku)
{
    $client_id     = $mkt_info['client_id']     ?? '';
    $client_secret = $mkt_info['client_secret'] ?? '';
    $auth_url      = $mkt_info['endpoints']['auth'] ?? 'https://api.cdiscount.com/api/1.0/auth/GenerateToken';
    $api_url       = $mkt_info['endpoints']['api']  ?? 'https://api.cdiscount.com/api/1.0';

    if (!$client_id || !$client_secret) {
        return array('ok' => false, 'msg' => 'Credentials Cdiscount manquants');
    }

    // 1. Obtenir le token OAuth2
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

    $auth_data   = json_decode($auth_resp, true);
    $token       = $auth_data['TokenId'] ?? ($auth_data['access_token'] ?? '');

    if (!$token) {
        return array('ok' => false, 'msg' => 'Token Cdiscount non reçu: '.$auth_resp);
    }

    // 2. Envoi du produit
    $product_body = array(
        'Products' => array(
            array_merge(array('SellerProductId' => $sku), $payload)
        )
    );
    $ch = curl_init($api_url.'/products');
    curl_setopt_array($ch, array(
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($product_body),
        CURLOPT_HTTPHEADER     => array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$token,
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) { return array('ok' => false, 'msg' => 'cURL: '.$err); }
    if ($code >= 400) { return array('ok' => false, 'msg' => 'HTTP '.$code.' — '.substr($resp, 0, 200)); }

    return array('ok' => true, 'msg' => 'Cdiscount OK (HTTP '.$code.')');
}

// ── Mirakl (API Key header) ───────────────────────────────────────────────────
function mkt_sync_mirakl($mkt_info, $payload, $sku)
{
    $api_key = $mkt_info['api_key'] ?? '';
    $api_url = $mkt_info['endpoints']['api'] ?? 'https://adeo-marketplace.mirakl.net/api';

    if (!$api_key) {
        return array('ok' => false, 'msg' => 'API Key Mirakl manquante');
    }

    // Mirakl P44 — mise à jour offre
    $offer_body = array(
        'offers' => array(
            array_merge(array(
                'sku'              => $sku,
                'product_sku'      => $sku,
                'state_code'       => '11', // Neuf
                'currency_iso_code'=> 'EUR',
            ), $payload)
        )
    );

    $ch = curl_init($api_url.'/offers');
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode($offer_body),
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

    if ($err) { return array('ok' => false, 'msg' => 'cURL: '.$err); }
    if ($code >= 400) { return array('ok' => false, 'msg' => 'HTTP '.$code.' — '.substr($resp, 0, 200)); }

    return array('ok' => true, 'msg' => 'Mirakl OK (HTTP '.$code.')');
}

// ── Amazon SP-API (OAuth2 LWA) ────────────────────────────────────────────────
function mkt_sync_amazon($mkt_info, $payload, $sku)
{
    $client_id     = $mkt_info['client_id']      ?? '';
    $client_secret = $mkt_info['client_secret']  ?? '';
    $refresh_token = $mkt_info['refresh_token']  ?? '';
    $auth_url      = $mkt_info['endpoints']['auth'] ?? 'https://api.amazon.com/auth/o2/token';
    $api_url       = $mkt_info['endpoints']['api']  ?? 'https://sellingpartnerapi-eu.amazon.com';
    $marketplace_id= $mkt_info['marketplace_id'] ?? 'A13V1IB3VIYZZH';

    if (!$client_id || !$client_secret || !$refresh_token) {
        return array('ok' => false, 'msg' => 'Credentials Amazon (client_id, client_secret, refresh_token) manquants');
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
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $auth_resp = curl_exec($ch);
    $auth_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $auth_err  = curl_error($ch);
    curl_close($ch);

    if ($auth_err || $auth_code >= 400) {
        return array('ok' => false, 'msg' => 'Auth Amazon échouée (HTTP '.$auth_code.') '.$auth_err);
    }

    $auth_data   = json_decode($auth_resp, true);
    $access_token = $auth_data['access_token'] ?? '';
    if (!$access_token) {
        return array('ok' => false, 'msg' => 'Access token Amazon non reçu: '.$auth_resp);
    }

    // 2. Patch listings SP-API
    $listing_body = array(
        'productType' => 'PRODUCT',
        'patches'     => array(
            array(
                'op'    => 'replace',
                'path'  => '/attributes',
                'value' => array_merge(array(
                    'condition_type' => array(array('value' => 'new_new', 'marketplace_id' => $marketplace_id)),
                ), array_map(function($v) use ($marketplace_id) {
                    return array(array('value' => $v, 'marketplace_id' => $marketplace_id));
                }, $payload))
            )
        )
    );

    $seller_id = $mkt_info['seller_id'] ?? '';
    $ch = curl_init($api_url.'/listings/2021-08-01/items/'.$seller_id.'/'.$sku.'?marketplaceIds='.$marketplace_id);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_POSTFIELDS     => json_encode($listing_body),
        CURLOPT_HTTPHEADER     => array(
            'Content-Type: application/json',
            'x-amz-access-token: '.$access_token,
        ),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) { return array('ok' => false, 'msg' => 'cURL: '.$err); }
    if ($code >= 400) { return array('ok' => false, 'msg' => 'HTTP '.$code.' — '.substr($resp, 0, 200)); }

    return array('ok' => true, 'msg' => 'Amazon OK (HTTP '.$code.')');
}

// ── Marketplace générique (REST JSON POST) ────────────────────────────────────
function mkt_sync_generic($mkt_info, $payload, $sku)
{
    $api_url  = $mkt_info['endpoints']['api'] ?? '';
    $api_key  = $mkt_info['api_key'] ?? '';
    $auth_type = $mkt_info['auth_type'] ?? 'apikey';

    $headers = array('Content-Type: application/json', 'Accept: application/json');
    if ($auth_type === 'apikey' && $api_key) {
        $headers[] = 'Authorization: '.$api_key;
    }

    $ch = curl_init($api_url.'/products/'.$sku);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ));
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) { return array('ok' => false, 'msg' => 'cURL: '.$err); }
    if ($code >= 400) { return array('ok' => false, 'msg' => 'HTTP '.$code.' — '.substr($resp, 0, 200)); }

    return array('ok' => true, 'msg' => 'OK (HTTP '.$code.')');
}
