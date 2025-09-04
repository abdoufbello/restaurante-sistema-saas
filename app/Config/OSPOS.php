<?php

namespace Config;

use App\Models\Appconfig;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Config\BaseConfig;

/**
 * This class holds the configuration options stored from the database so that on launch those settings can be cached
 * once in memory.  The settings are referenced frequently, so there is a significant performance hit to not storing
 * them.
 */
class OSPOS extends BaseConfig
{
    public array $settings;
    public string $commit_sha1 = 'dev';    // TODO: Travis scripts need to be updated to replace this with the commit hash on build
    private CacheInterface $cache;

    public function __construct()
    {
        parent::__construct();
        $this->cache = Services::cache();
        $this->set_settings();
    }

    /**
     * @return void
     */
    public function set_settings(): void
    {
        $cache = $this->cache->get('settings');

        if ($cache) {
            $this->settings = decode_array($cache);
        } else {
            // Check if database is enabled by checking if database settings are present in .env
            // and not commented out
            $envDbHostname = getenv('database.default.hostname');
            $envDbDatabase = getenv('database.default.database');
            
            // If database hostname or database name is not set in .env, 
            // or if they match the default values from Database.php, 
            // assume database is disabled
            if (empty($envDbHostname) || empty($envDbDatabase) || 
                ($envDbHostname === 'localhost' && $envDbDatabase === 'ospos')) {
                // Database is disabled, use default settings
                $this->settings = [
                    'language' => 'english',
                    'language_code' => 'en',
                    'timezone' => 'America/Sao_Paulo',
                    'dateformat' => 'm/d/Y',
                    'timeformat' => 'H:i:s',
                    'currency_symbol' => '$',
                    'currency_code' => 'USD',
                    'thousands_separator' => ',',
                    'decimal_point' => '.',
                    'currency_decimals' => '2',
                    'tax_decimals' => '2',
                    'quantity_decimals' => '2',
                    'receipt_show_taxes' => '1',
                    'receipt_show_total_discount' => '1',
                    'receipt_show_description' => '1',
                    'receipt_show_serialnumber' => '0',
                    'receipt_template' => 'receipt_default',
                    'barcode_type' => 'Code128',
                    'barcode_font' => 'Arial',
                    'barcode_font_size' => '10',
                    'barcode_width' => '250',
                    'barcode_height' => '50',
                    'barcode_first_row' => 'category',
                    'barcode_second_row' => 'item_code',
                    'barcode_third_row' => 'unit_price',
                    'company' => 'Your Company Name',
                    'address' => '',
                    'phone' => '',
                    'email' => '',
                    'fax' => '',
                    'website' => ''
                ];
            } else {
                // Database is enabled, try to connect
                try {
                    $appconfig = model(Appconfig::class);
                    foreach ($appconfig->get_all()->getResult() as $app_config) {
                        $this->settings[$app_config->key] = $app_config->value;
                    }
                } catch (\Exception $e) {
                    // Database connection failed, use default settings
                    $this->settings = [];
                }
            }
            
            $this->cache->save('settings', encode_array($this->settings));
        }
    }

    /**
     * @return void
     */
    public function update_settings(): void
    {
        $this->cache->delete('settings');
        $this->set_settings();
    }
}
