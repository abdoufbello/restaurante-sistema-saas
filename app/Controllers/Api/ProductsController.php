<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use App\Models\ProductModel;
use App\Models\CategoryModel;

/**
 * Controlador de Produtos para APIs RESTful
 */
class ProductsController extends BaseApiController
{
    protected $productModel;
    protected $categoryModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }
    
    /**
     * Listar produtos
     * GET /api/products
     */
    public function index()
    {
        try {
            // Verificar permissão
            $this->requirePermission('products.read');
            
            // Parâmetros de paginação e ordenação
            $pagination = $this->getPaginationParams();
            $sort = $this->getSortParams(['id', 'name', 'price', 'category_id', 'status', 'stock_quantity', 'created_at']);
            $dateFilters = $this->getDateFilters();
            
            // Filtros específicos
            $filters = [
                'search' => $this->request->getGet('search'),
                'category_id' => $this->request->getGet('category_id'),
                'status' => $this->request->getGet('status'),
                'min_price' => $this->request->getGet('min_price'),
                'max_price' => $this->request->getGet('max_price'),
                'in_stock' => $this->request->getGet('in_stock'),
                'featured' => $this->request->getGet('featured'),
                'restaurant_id' => $this->restaurantId // Multi-tenancy
            ];
            
            // Verificar cache
            $cacheKey = $this->generateCacheKey('products_list', array_merge($pagination, $sort, $filters, $dateFilters));
            $cachedData = $this->getFromCache($cacheKey);
            
            if ($cachedData) {
                return $this->respondWithPagination(
                    $cachedData['data'],
                    $cachedData['total'],
                    $pagination
                );
            }
            
            // Query base
            $query = $this->productModel->select([
                'products.id', 'products.restaurant_id', 'products.category_id', 'products.name',
                'products.description', 'products.price', 'products.cost_price', 'products.sku',
                'products.barcode', 'products.stock_quantity', 'products.min_stock_level',
                'products.unit', 'products.weight', 'products.dimensions', 'products.image',
                'products.gallery', 'products.status', 'products.featured', 'products.tags',
                'products.seo_title', 'products.seo_description', 'products.created_at',
                'categories.name as category_name'
            ])->join('categories', 'categories.id = products.category_id', 'left');
            
            // Aplicar filtro de multi-tenancy
            $query = $this->applyTenantFilter($query, 'products');
            
            // Aplicar filtros
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->groupStart()
                      ->like('products.name', $search)
                      ->orLike('products.description', $search)
                      ->orLike('products.sku', $search)
                      ->orLike('products.barcode', $search)
                      ->orLike('products.tags', $search)
                      ->groupEnd();
            }
            
            if (!empty($filters['category_id'])) {
                if (is_array($filters['category_id'])) {
                    $query->whereIn('products.category_id', $filters['category_id']);
                } else {
                    $query->where('products.category_id', $filters['category_id']);
                }
            }
            
            if (!empty($filters['status'])) {
                $query->where('products.status', $filters['status']);
            }
            
            if (!empty($filters['min_price'])) {
                $query->where('products.price >=', $filters['min_price']);
            }
            
            if (!empty($filters['max_price'])) {
                $query->where('products.price <=', $filters['max_price']);
            }
            
            if (!empty($filters['in_stock'])) {
                if ($filters['in_stock'] === 'true' || $filters['in_stock'] === '1') {
                    $query->where('products.stock_quantity >', 0);
                } else {
                    $query->where('products.stock_quantity', 0);
                }
            }
            
            if (!empty($filters['featured'])) {
                $query->where('products.featured', $filters['featured'] === 'true' || $filters['featured'] === '1' ? 1 : 0);
            }
            
            // Filtros de data
            if (!empty($dateFilters['created_from'])) {
                $query->where('products.created_at >=', $dateFilters['created_from']);
            }
            
            if (!empty($dateFilters['created_to'])) {
                $query->where('products.created_at <=', $dateFilters['created_to']);
            }
            
            // Contar total
            $total = $query->countAllResults(false);
            
            // Aplicar ordenação e paginação
            $products = $query->orderBy('products.' . $sort['sort_by'], $sort['sort_dir'])
                             ->limit($pagination['limit'], $pagination['offset'])
                             ->findAll();
            
            // Processar dados dos produtos
            foreach ($products as &$product) {
                $product['gallery'] = json_decode($product['gallery'] ?? '[]', true);
                $product['tags'] = json_decode($product['tags'] ?? '[]', true);
                $product['dimensions'] = json_decode($product['dimensions'] ?? '{}', true);
                $product['in_stock'] = (int) $product['stock_quantity'] > 0;
                $product['low_stock'] = (int) $product['stock_quantity'] <= (int) $product['min_stock_level'];
            }
            
            // Sanitizar dados
            $products = $this->sanitizeOutput($products);
            
            // Salvar no cache
            $this->saveToCache($cacheKey, [
                'data' => $products,
                'total' => $total
            ]);
            
            return $this->respondWithPagination($products, $total, $pagination);
            
        } catch (\Exception $e) {
            log_message('error', 'Products index error: ' . $e->getMessage());
            return $this->failServerError('Erro ao buscar produtos');
        }
    }
    
    /**
     * Mostrar produto específico
     * GET /api/products/{id}
     */
    public function show($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('products.read');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do produto é obrigatório']);
            }
            
            // Verificar cache
            $cacheKey = $this->generateCacheKey('product_detail', ['id' => $id]);
            $cachedProduct = $this->getFromCache($cacheKey);
            
            if ($cachedProduct) {
                return $this->respondSuccess($cachedProduct);
            }
            
            // Buscar produto
            $query = $this->productModel->select([
                'products.*',
                'categories.name as category_name',
                'categories.slug as category_slug'
            ])->join('categories', 'categories.id = products.category_id', 'left');
            
            // Aplicar filtro de multi-tenancy
            $query = $this->applyTenantFilter($query, 'products');
            
            $product = $query->find($id);
            
            if (!$product) {
                return $this->failNotFound('Produto não encontrado');
            }
            
            // Processar dados do produto
            $product['gallery'] = json_decode($product['gallery'] ?? '[]', true);
            $product['tags'] = json_decode($product['tags'] ?? '[]', true);
            $product['dimensions'] = json_decode($product['dimensions'] ?? '{}', true);
            $product['nutritional_info'] = json_decode($product['nutritional_info'] ?? '{}', true);
            $product['variants'] = json_decode($product['variants'] ?? '[]', true);
            $product['in_stock'] = (int) $product['stock_quantity'] > 0;
            $product['low_stock'] = (int) $product['stock_quantity'] <= (int) $product['min_stock_level'];
            
            // Sanitizar dados
            $product = $this->sanitizeOutput($product);
            
            // Salvar no cache
            $this->saveToCache($cacheKey, $product);
            
            return $this->respondSuccess($product);
            
        } catch (\Exception $e) {
            log_message('error', 'Products show error: ' . $e->getMessage());
            return $this->failServerError('Erro ao buscar produto');
        }
    }
    
    /**
     * Criar novo produto
     * POST /api/products
     */
    public function create()
    {
        try {
            // Verificar permissão
            $this->requirePermission('products.create');
            
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'category_id' => 'required|integer|greater_than[0]',
                'name' => 'required|string|max_length[255]',
                'description' => 'permit_empty|string',
                'price' => 'required|decimal|greater_than[0]',
                'cost_price' => 'permit_empty|decimal|greater_than_equal_to[0]',
                'sku' => 'permit_empty|string|max_length[100]',
                'barcode' => 'permit_empty|string|max_length[100]',
                'stock_quantity' => 'permit_empty|integer|greater_than_equal_to[0]',
                'min_stock_level' => 'permit_empty|integer|greater_than_equal_to[0]',
                'unit' => 'permit_empty|string|max_length[50]',
                'weight' => 'permit_empty|decimal|greater_than_equal_to[0]',
                'dimensions' => 'permit_empty|array',
                'image' => 'permit_empty|string|max_length[255]',
                'gallery' => 'permit_empty|array',
                'status' => 'permit_empty|in_list[active,inactive,draft]',
                'featured' => 'permit_empty|in_list[0,1]',
                'tags' => 'permit_empty|array',
                'seo_title' => 'permit_empty|string|max_length[255]',
                'seo_description' => 'permit_empty|string|max_length[500]',
                'nutritional_info' => 'permit_empty|array',
                'variants' => 'permit_empty|array'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Verificar se a categoria existe
            $category = $this->categoryModel->where('restaurant_id', $this->restaurantId)
                                          ->find($validatedData['category_id']);
            
            if (!$category) {
                return $this->failValidationErrors(['category_id' => 'Categoria não encontrada']);
            }
            
            // Verificar SKU único (se fornecido)
            if (!empty($validatedData['sku'])) {
                $existingSku = $this->productModel->where('restaurant_id', $this->restaurantId)
                                                 ->where('sku', $validatedData['sku'])
                                                 ->first();
                
                if ($existingSku) {
                    return $this->failValidationErrors(['sku' => 'SKU já existe']);
                }
            }
            
            // Preparar dados do produto
            $productData = [
                'restaurant_id' => $this->restaurantId,
                'category_id' => $validatedData['category_id'],
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'price' => $validatedData['price'],
                'cost_price' => $validatedData['cost_price'] ?? null,
                'sku' => $validatedData['sku'] ?? null,
                'barcode' => $validatedData['barcode'] ?? null,
                'stock_quantity' => $validatedData['stock_quantity'] ?? 0,
                'min_stock_level' => $validatedData['min_stock_level'] ?? 0,
                'unit' => $validatedData['unit'] ?? null,
                'weight' => $validatedData['weight'] ?? null,
                'dimensions' => json_encode($validatedData['dimensions'] ?? []),
                'image' => $validatedData['image'] ?? null,
                'gallery' => json_encode($validatedData['gallery'] ?? []),
                'status' => $validatedData['status'] ?? 'active',
                'featured' => $validatedData['featured'] ?? 0,
                'tags' => json_encode($validatedData['tags'] ?? []),
                'seo_title' => $validatedData['seo_title'] ?? null,
                'seo_description' => $validatedData['seo_description'] ?? null,
                'nutritional_info' => json_encode($validatedData['nutritional_info'] ?? []),
                'variants' => json_encode($validatedData['variants'] ?? []),
                'created_by' => $this->currentUser['user_id']
            ];
            
            // Gerar slug se não fornecido
            if (empty($productData['slug'])) {
                $productData['slug'] = $this->generateSlug($validatedData['name']);
            }
            
            // Criar produto
            $productId = $this->productModel->insert($productData);
            
            if (!$productId) {
                return $this->failServerError('Erro ao criar produto');
            }
            
            // Buscar produto criado
            $product = $this->productModel->find($productId);
            
            // Processar dados do produto
            $product['gallery'] = json_decode($product['gallery'] ?? '[]', true);
            $product['tags'] = json_decode($product['tags'] ?? '[]', true);
            $product['dimensions'] = json_decode($product['dimensions'] ?? '{}', true);
            $product['nutritional_info'] = json_decode($product['nutritional_info'] ?? '{}', true);
            $product['variants'] = json_decode($product['variants'] ?? '[]', true);
            
            $product = $this->sanitizeOutput($product);
            
            // Limpar cache relacionado
            $this->deleteFromCache($this->generateCacheKey('products_list'));
            
            // Log da atividade
            $this->logActivity('product_create', [
                'product_id' => $productId,
                'product_name' => $product['name'],
                'category_id' => $product['category_id']
            ]);
            
            return $this->respondSuccess($product, 'Produto criado com sucesso', 201);
            
        } catch (\Exception $e) {
            log_message('error', 'Products create error: ' . $e->getMessage());
            return $this->failServerError('Erro ao criar produto');
        }
    }
    
    /**
     * Atualizar produto
     * PUT /api/products/{id}
     */
    public function update($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('products.update');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do produto é obrigatório']);
            }
            
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Buscar produto existente
            $query = $this->productModel->select('*');
            $query = $this->applyTenantFilter($query);
            $existingProduct = $query->find($id);
            
            if (!$existingProduct) {
                return $this->failNotFound('Produto não encontrado');
            }
            
            // Validar dados de entrada
            $rules = [
                'category_id' => 'permit_empty|integer|greater_than[0]',
                'name' => 'permit_empty|string|max_length[255]',
                'description' => 'permit_empty|string',
                'price' => 'permit_empty|decimal|greater_than[0]',
                'cost_price' => 'permit_empty|decimal|greater_than_equal_to[0]',
                'sku' => 'permit_empty|string|max_length[100]',
                'barcode' => 'permit_empty|string|max_length[100]',
                'stock_quantity' => 'permit_empty|integer|greater_than_equal_to[0]',
                'min_stock_level' => 'permit_empty|integer|greater_than_equal_to[0]',
                'unit' => 'permit_empty|string|max_length[50]',
                'weight' => 'permit_empty|decimal|greater_than_equal_to[0]',
                'dimensions' => 'permit_empty|array',
                'image' => 'permit_empty|string|max_length[255]',
                'gallery' => 'permit_empty|array',
                'status' => 'permit_empty|in_list[active,inactive,draft]',
                'featured' => 'permit_empty|in_list[0,1]',
                'tags' => 'permit_empty|array',
                'seo_title' => 'permit_empty|string|max_length[255]',
                'seo_description' => 'permit_empty|string|max_length[500]',
                'nutritional_info' => 'permit_empty|array',
                'variants' => 'permit_empty|array'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Verificar categoria se fornecida
            if (isset($validatedData['category_id'])) {
                $category = $this->categoryModel->where('restaurant_id', $this->restaurantId)
                                              ->find($validatedData['category_id']);
                
                if (!$category) {
                    return $this->failValidationErrors(['category_id' => 'Categoria não encontrada']);
                }
            }
            
            // Verificar SKU único (se fornecido e diferente do atual)
            if (!empty($validatedData['sku']) && $validatedData['sku'] !== $existingProduct['sku']) {
                $existingSku = $this->productModel->where('restaurant_id', $this->restaurantId)
                                                 ->where('sku', $validatedData['sku'])
                                                 ->where('id !=', $id)
                                                 ->first();
                
                if ($existingSku) {
                    return $this->failValidationErrors(['sku' => 'SKU já existe']);
                }
            }
            
            // Preparar dados para atualização
            $updateData = [];
            
            $allowedFields = [
                'category_id', 'name', 'description', 'price', 'cost_price', 'sku', 'barcode',
                'stock_quantity', 'min_stock_level', 'unit', 'weight', 'image', 'status',
                'featured', 'seo_title', 'seo_description'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($validatedData[$field])) {
                    $updateData[$field] = $validatedData[$field];
                }
            }
            
            // Campos JSON
            $jsonFields = ['dimensions', 'gallery', 'tags', 'nutritional_info', 'variants'];
            
            foreach ($jsonFields as $field) {
                if (isset($validatedData[$field])) {
                    $updateData[$field] = json_encode($validatedData[$field]);
                }
            }
            
            if (!empty($updateData)) {
                $updateData['updated_by'] = $this->currentUser['user_id'];
                
                // Atualizar produto
                $updated = $this->productModel->update($id, $updateData);
                
                if (!$updated) {
                    return $this->failServerError('Erro ao atualizar produto');
                }
            }
            
            // Buscar dados atualizados
            $product = $this->productModel->find($id);
            
            // Processar dados do produto
            $product['gallery'] = json_decode($product['gallery'] ?? '[]', true);
            $product['tags'] = json_decode($product['tags'] ?? '[]', true);
            $product['dimensions'] = json_decode($product['dimensions'] ?? '{}', true);
            $product['nutritional_info'] = json_decode($product['nutritional_info'] ?? '{}', true);
            $product['variants'] = json_decode($product['variants'] ?? '[]', true);
            
            $product = $this->sanitizeOutput($product);
            
            // Limpar cache relacionado
            $this->deleteFromCache($this->generateCacheKey('products_list'));
            $this->deleteFromCache($this->generateCacheKey('product_detail', ['id' => $id]));
            
            // Log da atividade
            $this->logActivity('product_update', [
                'product_id' => $id,
                'updated_fields' => array_keys($updateData)
            ]);
            
            return $this->respondSuccess($product, 'Produto atualizado com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Products update error: ' . $e->getMessage());
            return $this->failServerError('Erro ao atualizar produto');
        }
    }
    
    /**
     * Excluir produto
     * DELETE /api/products/{id}
     */
    public function delete($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('products.delete');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do produto é obrigatório']);
            }
            
            // Buscar produto existente
            $query = $this->productModel->select('*');
            $query = $this->applyTenantFilter($query);
            $product = $query->find($id);
            
            if (!$product) {
                return $this->failNotFound('Produto não encontrado');
            }
            
            // Verificar se o produto está sendo usado em pedidos
            // TODO: Implementar verificação de dependências
            
            // Soft delete
            $deleted = $this->productModel->delete($id);
            
            if (!$deleted) {
                return $this->failServerError('Erro ao excluir produto');
            }
            
            // Limpar cache relacionado
            $this->deleteFromCache($this->generateCacheKey('products_list'));
            $this->deleteFromCache($this->generateCacheKey('product_detail', ['id' => $id]));
            
            // Log da atividade
            $this->logActivity('product_delete', [
                'product_id' => $id,
                'product_name' => $product['name']
            ]);
            
            return $this->respondSuccess(null, 'Produto excluído com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Products delete error: ' . $e->getMessage());
            return $this->failServerError('Erro ao excluir produto');
        }
    }
    
    /**
     * Atualizar estoque do produto
     * PUT /api/products/{id}/stock
     */
    public function updateStock($id = null)
    {
        try {
            // Verificar permissão
            $this->requirePermission('products.update');
            
            if (!$id) {
                return $this->failValidationErrors(['id' => 'ID do produto é obrigatório']);
            }
            
            $input = $this->request->getJSON(true) ?: $this->request->getPost();
            
            // Validar dados de entrada
            $rules = [
                'quantity' => 'required|integer',
                'operation' => 'required|in_list[set,add,subtract]',
                'reason' => 'permit_empty|string|max_length[255]'
            ];
            
            $validatedData = $this->validateInput($input, $rules);
            
            // Buscar produto existente
            $query = $this->productModel->select('*');
            $query = $this->applyTenantFilter($query);
            $product = $query->find($id);
            
            if (!$product) {
                return $this->failNotFound('Produto não encontrado');
            }
            
            $currentStock = (int) $product['stock_quantity'];
            $newStock = $currentStock;
            
            // Calcular novo estoque
            switch ($validatedData['operation']) {
                case 'set':
                    $newStock = $validatedData['quantity'];
                    break;
                case 'add':
                    $newStock = $currentStock + $validatedData['quantity'];
                    break;
                case 'subtract':
                    $newStock = $currentStock - $validatedData['quantity'];
                    break;
            }
            
            // Verificar se o estoque não fica negativo
            if ($newStock < 0) {
                return $this->failValidationErrors(['quantity' => 'Estoque não pode ficar negativo']);
            }
            
            // Atualizar estoque
            $updated = $this->productModel->update($id, [
                'stock_quantity' => $newStock,
                'updated_by' => $this->currentUser['user_id']
            ]);
            
            if (!$updated) {
                return $this->failServerError('Erro ao atualizar estoque');
            }
            
            // Limpar cache relacionado
            $this->deleteFromCache($this->generateCacheKey('products_list'));
            $this->deleteFromCache($this->generateCacheKey('product_detail', ['id' => $id]));
            
            // Log da atividade
            $this->logActivity('product_stock_update', [
                'product_id' => $id,
                'product_name' => $product['name'],
                'operation' => $validatedData['operation'],
                'quantity' => $validatedData['quantity'],
                'previous_stock' => $currentStock,
                'new_stock' => $newStock,
                'reason' => $validatedData['reason'] ?? null
            ]);
            
            return $this->respondSuccess([
                'previous_stock' => $currentStock,
                'new_stock' => $newStock,
                'operation' => $validatedData['operation'],
                'quantity' => $validatedData['quantity']
            ], 'Estoque atualizado com sucesso');
            
        } catch (\Exception $e) {
            log_message('error', 'Products update stock error: ' . $e->getMessage());
            return $this->failServerError('Erro ao atualizar estoque');
        }
    }
    
    /**
     * Buscar produtos
     * GET /api/products/search
     */
    public function search()
    {
        try {
            // Verificar permissão
            $this->requirePermission('products.read');
            
            $query = $this->request->getGet('q');
            $limit = min(50, max(1, (int) $this->request->getGet('limit') ?: 10));
            
            if (empty($query) || strlen($query) < 2) {
                return $this->failValidationErrors(['q' => 'Termo de busca deve ter pelo menos 2 caracteres']);
            }
            
            // Buscar produtos
            $queryBuilder = $this->productModel->select([
                'id', 'name', 'description', 'price', 'sku', 'image', 'stock_quantity', 'status'
            ]);
            
            // Aplicar filtro de multi-tenancy
            $queryBuilder = $this->applyTenantFilter($queryBuilder);
            
            $products = $queryBuilder->groupStart()
                                    ->like('name', $query)
                                    ->orLike('description', $query)
                                    ->orLike('sku', $query)
                                    ->orLike('barcode', $query)
                                    ->groupEnd()
                                    ->where('status', 'active')
                                    ->limit($limit)
                                    ->findAll();
            
            // Processar dados dos produtos
            foreach ($products as &$product) {
                $product['in_stock'] = (int) $product['stock_quantity'] > 0;
            }
            
            // Sanitizar dados
            $products = $this->sanitizeOutput($products);
            
            return $this->respondSuccess($products);
            
        } catch (\Exception $e) {
            log_message('error', 'Products search error: ' . $e->getMessage());
            return $this->failServerError('Erro ao buscar produtos');
        }
    }
    
    /**
     * Estatísticas de produtos
     * GET /api/products/stats
     */
    public function stats()
    {
        try {
            // Verificar permissão
            $this->requirePermission('products.read');
            
            // Verificar cache
            $cacheKey = $this->generateCacheKey('products_stats');
            $cachedStats = $this->getFromCache($cacheKey);
            
            if ($cachedStats) {
                return $this->respondSuccess($cachedStats);
            }
            
            // Query base com filtro de tenant
            $query = $this->productModel->select('*');
            $query = $this->applyTenantFilter($query);
            
            // Estatísticas gerais
            $stats = $query->select([
                'COUNT(*) as total_products',
                'COUNT(CASE WHEN status = "active" THEN 1 END) as active_products',
                'COUNT(CASE WHEN status = "inactive" THEN 1 END) as inactive_products',
                'COUNT(CASE WHEN status = "draft" THEN 1 END) as draft_products',
                'COUNT(CASE WHEN featured = 1 THEN 1 END) as featured_products',
                'COUNT(CASE WHEN stock_quantity > 0 THEN 1 END) as in_stock_products',
                'COUNT(CASE WHEN stock_quantity = 0 THEN 1 END) as out_of_stock_products',
                'COUNT(CASE WHEN stock_quantity <= min_stock_level THEN 1 END) as low_stock_products',
                'AVG(price) as average_price',
                'MIN(price) as min_price',
                'MAX(price) as max_price',
                'SUM(stock_quantity) as total_stock_quantity'
            ])->first();
            
            // Produtos por categoria
            $byCategory = $query->select([
                'categories.name as category_name',
                'COUNT(products.id) as count'
            ])->join('categories', 'categories.id = products.category_id', 'left')
              ->groupBy('products.category_id')
              ->orderBy('count', 'DESC')
              ->findAll();
            
            // Produtos com baixo estoque
            $lowStock = $query->select([
                'id', 'name', 'sku', 'stock_quantity', 'min_stock_level'
            ])->where('stock_quantity <= min_stock_level')
              ->where('status', 'active')
              ->orderBy('stock_quantity', 'ASC')
              ->limit(10)
              ->findAll();
            
            $result = [
                'general' => $stats,
                'by_category' => $byCategory,
                'low_stock' => $this->sanitizeOutput($lowStock)
            ];
            
            // Salvar no cache por 10 minutos
            $this->saveToCache($cacheKey, $result, 600);
            
            return $this->respondSuccess($result);
            
        } catch (\Exception $e) {
            log_message('error', 'Products stats error: ' . $e->getMessage());
            return $this->failServerError('Erro ao obter estatísticas');
        }
    }
    
    // ========================================
    // MÉTODOS AUXILIARES
    // ========================================
    
    /**
     * Gerar slug único para o produto
     */
    private function generateSlug(string $name): string
    {
        $slug = url_title($name, '-', true);
        $originalSlug = $slug;
        $counter = 1;
        
        // Verificar se o slug já existe
        while ($this->productModel->where('restaurant_id', $this->restaurantId)
                                 ->where('slug', $slug)
                                 ->first()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}