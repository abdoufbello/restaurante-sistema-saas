<?php

namespace App\Controllers;

use App\Libraries\Barcode_lib;
use App\Models\Appconfig;
use App\Models\Employee;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Supplier;
use CodeIgniter\Controller;

class Purchases extends Secure_Controller
{
    public function __construct()
    {
        parent::__construct('purchases');
        
        $this->load->model('Purchase');
        $this->load->model('Supplier');
        $this->load->model('Item');
    }

    public function index($offset = 0)
    {
        $config = $this->_get_config();
        
        $data['controller_name'] = $this->lang->line('purchases_register');
        $data['form_width'] = $this->get_form_width();
        $data['purchases'] = $this->Purchase->get_all($config['per_page'], $offset)->result();
        $data['total_rows'] = $this->Purchase->count_all();
        $data['pagination'] = $this->pagination->create_links();
        
        $this->load->view('purchases/manage', $data);
    }

    public function register()
    {
        $data = array();
        $data['cart'] = $this->purchase->get_cart();
        $data['modes'] = array(
            'purchase' => $this->lang->line('purchases_register'),
            'return' => $this->lang->line('purchases_return')
        );
        $data['mode'] = 'purchase';
        $data['suppliers'] = array();
        $data['controller_name'] = $this->lang->line('purchases_register');
        
        $this->load->view('purchases/register', $data);
    }

    public function add_item()
    {
        $item_id = $this->input->post('item');
        $quantity = $this->input->post('quantity');
        $cost_price = $this->input->post('cost_price');
        $discount = $this->input->post('discount');
        
        if ($this->Item->exists($item_id))
        {
            $item_info = $this->Item->get_info($item_id);
            
            $insert_data = array(
                'item_id' => $item_id,
                'quantity' => $quantity,
                'cost_price' => $cost_price,
                'discount_percent' => $discount,
                'item_location' => $item_info->item_location ?? 1
            );
            
            $this->purchase->add_item($insert_data);
            
            echo json_encode(array('success' => TRUE));
        }
        else
        {
            echo json_encode(array('success' => FALSE, 'message' => $this->lang->line('purchases_unable_to_add_item')));
        }
    }

    public function delete_item()
    {
        $item_id = $this->input->post('item');
        $this->purchase->delete_item($item_id);
        
        echo json_encode(array('success' => TRUE));
    }

    public function complete()
    {
        $data = array();
        $data['cart'] = $this->purchase->get_cart();
        $data['total'] = $this->purchase->get_total();
        $data['supplier_id'] = $this->input->post('supplier_id');
        $data['reference'] = $this->input->post('reference');
        $data['comment'] = $this->input->post('comment');
        
        if ($this->purchase->save($data))
        {
            // Update item quantities in stock
            foreach ($data['cart'] as $item)
            {
                $this->Item->update_quantity($item['item_id'], $item['quantity']);
            }
            
            $this->purchase->clear_cart();
            
            $data['success'] = TRUE;
            $data['purchase_id'] = $this->purchase->get_last_purchase_id();
        }
        else
        {
            $data['success'] = FALSE;
            $data['message'] = $this->lang->line('purchases_transaction_failed');
        }
        
        $this->load->view('purchases/receipt', $data);
    }

    public function receipt($purchase_id)
    {
        $data['purchase_info'] = $this->Purchase->get_info($purchase_id);
        $data['purchase_items'] = $this->Purchase->get_purchase_items($purchase_id)->result();
        
        $this->load->view('purchases/receipt', $data);
    }

    public function delete($purchase_id = -1)
    {
        $deleted = 0;
        
        if ($this->input->post('ids'))
        {
            $purchase_ids = $this->input->post('ids');
            
            foreach ($purchase_ids as $purchase_id)
            {
                if ($this->Purchase->delete($purchase_id))
                {
                    $deleted++;
                }
            }
        }
        else
        {
            if ($this->Purchase->delete($purchase_id))
            {
                $deleted = 1;
            }
        }
        
        echo json_encode(array('success' => $deleted > 0, 'deleted_count' => $deleted));
    }

    public function search()
    {
        $search = $this->input->get('term');
        $suggestions = $this->Purchase->get_search_suggestions($search, 25);
        
        echo json_encode($suggestions);
    }

    private function _get_config()
    {
        $config['base_url'] = site_url('purchases/index');
        $config['total_rows'] = $this->Purchase->count_all();
        $config['per_page'] = $this->Appconfig->get('lines_per_page');
        $config['uri_segment'] = 3;
        
        $this->pagination->initialize($config);
        
        return $config;
    }

    private function get_form_width()
    {
        return $this->Appconfig->get('form_width') ?? 'col-md-12';
    }
}