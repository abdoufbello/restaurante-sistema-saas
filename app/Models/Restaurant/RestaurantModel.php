<?php

namespace App\Models\Restaurant;

use CodeIgniter\Model;

/**
 * Restaurant Model
 * Manages restaurant data for the Kiosk system
 */
class RestaurantModel extends Model
{
    protected $table = 'restaurants';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'cnpj',
        'name',
        'trade_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'logo',
        'status',
        'subscription_plan',
        'subscription_expires_at',
        'settings'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'cnpj' => 'required|is_unique[restaurants.cnpj,id,{id}]|min_length[14]|max_length[14]',
        'name' => 'required|min_length[3]|max_length[255]',
        'trade_name' => 'permit_empty|max_length[255]',
        'email' => 'required|valid_email|is_unique[restaurants.email,id,{id}]',
        'phone' => 'required|min_length[10]|max_length[15]',
        'address' => 'required|min_length[10]|max_length[500]',
        'city' => 'required|min_length[2]|max_length[100]',
        'state' => 'required|exact_length[2]',
        'zip_code' => 'required|exact_length[8]',
        'status' => 'required|in_list[active,inactive,suspended]',
        'subscription_plan' => 'required|in_list[basic,premium,enterprise]'
    ];

    protected $validationMessages = [
        'cnpj' => [
            'required' => 'CNPJ é obrigatório',
            'is_unique' => 'Este CNPJ já está cadastrado',
            'min_length' => 'CNPJ deve ter 14 dígitos',
            'max_length' => 'CNPJ deve ter 14 dígitos'
        ],
        'name' => [
            'required' => 'Nome da empresa é obrigatório',
            'min_length' => 'Nome deve ter pelo menos 3 caracteres',
            'max_length' => 'Nome deve ter no máximo 255 caracteres'
        ],
        'email' => [
            'required' => 'Email é obrigatório',
            'valid_email' => 'Email deve ser válido',
            'is_unique' => 'Este email já está cadastrado'
        ],
        'phone' => [
            'required' => 'Telefone é obrigatório',
            'min_length' => 'Telefone deve ter pelo menos 10 dígitos',
            'max_length' => 'Telefone deve ter no máximo 15 dígitos'
        ],
        'address' => [
            'required' => 'Endereço é obrigatório',
            'min_length' => 'Endereço deve ter pelo menos 10 caracteres',
            'max_length' => 'Endereço deve ter no máximo 500 caracteres'
        ],
        'city' => [
            'required' => 'Cidade é obrigatória',
            'min_length' => 'Cidade deve ter pelo menos 2 caracteres',
            'max_length' => 'Cidade deve ter no máximo 100 caracteres'
        ],
        'state' => [
            'required' => 'Estado é obrigatório',
            'exact_length' => 'Estado deve ter 2 caracteres (UF)'
        ],
        'zip_code' => [
            'required' => 'CEP é obrigatório',
            'exact_length' => 'CEP deve ter 8 dígitos'
        ],
        'status' => [
            'required' => 'Status é obrigatório',
            'in_list' => 'Status deve ser: active, inactive ou suspended'
        ],
        'subscription_plan' => [
            'required' => 'Plano de assinatura é obrigatório',
            'in_list' => 'Plano deve ser: basic, premium ou enterprise'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['beforeInsert'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['beforeUpdate'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['afterFind'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Before insert callback
     */
    protected function beforeInsert(array $data)
    {
        // Clean CNPJ
        if (isset($data['data']['cnpj'])) {
            $data['data']['cnpj'] = preg_replace('/[^0-9]/', '', $data['data']['cnpj']);
        }

        // Clean phone
        if (isset($data['data']['phone'])) {
            $data['data']['phone'] = preg_replace('/[^0-9]/', '', $data['data']['phone']);
        }

        // Clean zip_code
        if (isset($data['data']['zip_code'])) {
            $data['data']['zip_code'] = preg_replace('/[^0-9]/', '', $data['data']['zip_code']);
        }

        // Set default settings
        if (!isset($data['data']['settings'])) {
            $data['data']['settings'] = json_encode([
                'currency' => 'BRL',
                'timezone' => 'America/Sao_Paulo',
                'language' => 'pt-BR',
                'tax_rate' => 0.00,
                'service_fee' => 0.00,
                'kiosk_theme' => 'default',
                'printer_enabled' => true,
                'scanner_enabled' => true,
                'payment_methods' => [
                    'pix' => true,
                    'credit_card' => true,
                    'debit_card' => true,
                    'cash' => true
                ]
            ]);
        }

        return $data;
    }

    /**
     * Before update callback
     */
    protected function beforeUpdate(array $data)
    {
        // Clean CNPJ
        if (isset($data['data']['cnpj'])) {
            $data['data']['cnpj'] = preg_replace('/[^0-9]/', '', $data['data']['cnpj']);
        }

        // Clean phone
        if (isset($data['data']['phone'])) {
            $data['data']['phone'] = preg_replace('/[^0-9]/', '', $data['data']['phone']);
        }

        // Clean zip_code
        if (isset($data['data']['zip_code'])) {
            $data['data']['zip_code'] = preg_replace('/[^0-9]/', '', $data['data']['zip_code']);
        }

        return $data;
    }

    /**
     * After find callback
     */
    protected function afterFind(array $data)
    {
        if (isset($data['data'])) {
            // Single record
            if (isset($data['data']['settings']) && is_string($data['data']['settings'])) {
                $data['data']['settings'] = json_decode($data['data']['settings'], true);
            }
        } else {
            // Multiple records
            foreach ($data as &$record) {
                if (isset($record['settings']) && is_string($record['settings'])) {
                    $record['settings'] = json_decode($record['settings'], true);
                }
            }
        }

        return $data;
    }

    /**
     * Get restaurant by CNPJ
     */
    public function getByCNPJ($cnpj)
    {
        $cleanCNPJ = preg_replace('/[^0-9]/', '', $cnpj);
        return $this->where('cnpj', $cleanCNPJ)->first();
    }

    /**
     * Get active restaurants
     */
    public function getActive()
    {
        return $this->where('status', 'active')->findAll();
    }

    /**
     * Check if subscription is valid
     */
    public function isSubscriptionValid($restaurantId)
    {
        $restaurant = $this->find($restaurantId);
        
        if (!$restaurant) {
            return false;
        }

        if ($restaurant['subscription_expires_at'] === null) {
            return true; // No expiration date means unlimited
        }

        return strtotime($restaurant['subscription_expires_at']) > time();
    }

    /**
     * Update restaurant settings
     */
    public function updateSettings($restaurantId, array $settings)
    {
        $restaurant = $this->find($restaurantId);
        
        if (!$restaurant) {
            return false;
        }

        $currentSettings = $restaurant['settings'] ?? [];
        $newSettings = array_merge($currentSettings, $settings);

        return $this->update($restaurantId, ['settings' => json_encode($newSettings)]);
    }

    /**
     * Get restaurant statistics
     */
    public function getStatistics($restaurantId)
    {
        $db = \Config\Database::connect();
        
        // Get total orders
        $totalOrders = $db->table('orders')
            ->where('restaurant_id', $restaurantId)
            ->countAllResults();

        // Get total revenue
        $totalRevenue = $db->table('orders')
            ->selectSum('total_amount')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->get()
            ->getRow()
            ->total_amount ?? 0;

        // Get total dishes
        $totalDishes = $db->table('dishes')
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'active')
            ->countAllResults();

        // Get orders today
        $ordersToday = $db->table('orders')
            ->where('restaurant_id', $restaurantId)
            ->where('DATE(created_at)', date('Y-m-d'))
            ->countAllResults();

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'total_dishes' => $totalDishes,
            'orders_today' => $ordersToday
        ];
    }
}