<?php

namespace App\Controllers\Api;

use App\Controllers\Api\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;

class UploadsController extends BaseApiController
{
    protected $requiredPermissions = [
        'image' => 'uploads.create',
        'file' => 'uploads.create',
        'delete' => 'uploads.delete',
        'list' => 'uploads.read'
    ];

    private $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private $allowedFileTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'];
    private $maxImageSize = 5 * 1024 * 1024; // 5MB
    private $maxFileSize = 10 * 1024 * 1024; // 10MB

    /**
     * Upload de imagens
     */
    public function image()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('uploads.create');
            
            $file = $this->request->getFile('image');
            
            if (!$file || !$file->isValid()) {
                return $this->validationErrorResponse(['image' => 'Arquivo de imagem é obrigatório']);
            }
            
            // Validações básicas
            $validation = $this->validateImageFile($file);
            if ($validation !== true) {
                return $this->validationErrorResponse(['image' => $validation]);
            }
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            $userId = $this->getCurrentUser()['id'];
            
            // Verifica limites do plano
            if (!$this->checkStorageLimit($restaurantId, $file->getSize())) {
                return $this->forbiddenResponse('Limite de armazenamento do plano atingido');
            }
            
            // Gera nome único
            $extension = $file->getClientExtension();
            $filename = $this->generateUniqueFilename($extension);
            
            // Define diretórios
            $uploadPath = WRITEPATH . 'uploads/images/' . $restaurantId . '/';
            $publicPath = '/uploads/images/' . $restaurantId . '/';
            
            // Cria diretório se não existir
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Move arquivo
            if (!$file->move($uploadPath, $filename)) {
                return $this->errorResponse('Erro ao fazer upload da imagem');
            }
            
            $filePath = $uploadPath . $filename;
            
            // Otimiza imagem
            $this->optimizeImage($filePath, $extension);
            
            // Gera thumbnails
            $thumbnails = $this->generateThumbnails($filePath, $uploadPath, $filename, $extension);
            
            // Salva informações no banco
            $db = \Config\Database::connect();
            $uploadData = [
                'restaurant_id' => $restaurantId,
                'user_id' => $userId,
                'type' => 'image',
                'original_name' => $file->getClientName(),
                'filename' => $filename,
                'file_path' => $publicPath . $filename,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'extension' => $extension,
                'thumbnails' => json_encode($thumbnails),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $db->table('uploads')->insert($uploadData);
            $uploadId = $db->insertID();
            
            // Atualiza estatísticas de uso
            $this->updateStorageUsage($restaurantId, $file->getSize());
            
            $this->logActivity('image_upload', [
                'upload_id' => $uploadId,
                'filename' => $filename,
                'size' => $file->getSize()
            ]);
            
            return $this->successResponse([
                'id' => $uploadId,
                'filename' => $filename,
                'original_name' => $file->getClientName(),
                'url' => base_url($publicPath . $filename),
                'thumbnails' => array_map(function($thumb) use ($publicPath) {
                    return [
                        'size' => $thumb['size'],
                        'url' => base_url($publicPath . $thumb['filename'])
                    ];
                }, $thumbnails),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType()
            ], 'Imagem enviada com sucesso', 201);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao fazer upload da imagem: ' . $e->getMessage());
        }
    }
    
    /**
     * Upload de arquivos
     */
    public function file()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('uploads.create');
            
            $file = $this->request->getFile('file');
            
            if (!$file || !$file->isValid()) {
                return $this->validationErrorResponse(['file' => 'Arquivo é obrigatório']);
            }
            
            // Validações básicas
            $validation = $this->validateFile($file);
            if ($validation !== true) {
                return $this->validationErrorResponse(['file' => $validation]);
            }
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            $userId = $this->getCurrentUser()['id'];
            
            // Verifica limites do plano
            if (!$this->checkStorageLimit($restaurantId, $file->getSize())) {
                return $this->forbiddenResponse('Limite de armazenamento do plano atingido');
            }
            
            // Gera nome único
            $extension = $file->getClientExtension();
            $filename = $this->generateUniqueFilename($extension);
            
            // Define diretórios
            $uploadPath = WRITEPATH . 'uploads/files/' . $restaurantId . '/';
            $publicPath = '/uploads/files/' . $restaurantId . '/';
            
            // Cria diretório se não existir
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Move arquivo
            if (!$file->move($uploadPath, $filename)) {
                return $this->errorResponse('Erro ao fazer upload do arquivo');
            }
            
            // Salva informações no banco
            $db = \Config\Database::connect();
            $uploadData = [
                'restaurant_id' => $restaurantId,
                'user_id' => $userId,
                'type' => 'file',
                'original_name' => $file->getClientName(),
                'filename' => $filename,
                'file_path' => $publicPath . $filename,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'extension' => $extension,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $db->table('uploads')->insert($uploadData);
            $uploadId = $db->insertID();
            
            // Atualiza estatísticas de uso
            $this->updateStorageUsage($restaurantId, $file->getSize());
            
            $this->logActivity('file_upload', [
                'upload_id' => $uploadId,
                'filename' => $filename,
                'size' => $file->getSize()
            ]);
            
            return $this->successResponse([
                'id' => $uploadId,
                'filename' => $filename,
                'original_name' => $file->getClientName(),
                'url' => base_url($publicPath . $filename),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType()
            ], 'Arquivo enviado com sucesso', 201);
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao fazer upload do arquivo: ' . $e->getMessage());
        }
    }
    
    /**
     * Lista uploads
     */
    public function list()
    {
        try {
            $this->validateJWT();
            $this->checkPermission('uploads.read');
            
            $page = (int) ($this->request->getGet('page') ?? 1);
            $perPage = min((int) ($this->request->getGet('per_page') ?? 20), 100);
            $type = $this->request->getGet('type'); // image, file
            $search = $this->request->getGet('search');
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            $db = \Config\Database::connect();
            $builder = $db->table('uploads');
            
            // Aplica filtro de multi-tenancy
            $builder->where('restaurant_id', $restaurantId);
            
            // Aplica filtros
            if ($type) {
                $builder->where('type', $type);
            }
            
            if ($search) {
                $builder->groupStart()
                    ->like('original_name', $search)
                    ->orLike('filename', $search)
                    ->groupEnd();
            }
            
            // Conta total
            $total = $builder->countAllResults(false);
            
            // Aplica paginação e ordenação
            $uploads = $builder
                ->orderBy('created_at', 'DESC')
                ->limit($perPage, ($page - 1) * $perPage)
                ->get()
                ->getResultArray();
            
            // Processa dados
            $uploads = array_map(function($upload) {
                // Decodifica thumbnails se existirem
                if ($upload['thumbnails']) {
                    $upload['thumbnails'] = json_decode($upload['thumbnails'], true);
                }
                
                // Adiciona URL completa
                $upload['full_url'] = base_url($upload['file_path']);
                
                // Sanitiza dados
                return $this->sanitizeOutputData($upload);
            }, $uploads);
            
            $data = [
                'uploads' => $uploads,
                'pagination' => $this->buildPaginationData($page, $perPage, $total)
            ];
            
            return $this->successResponse($data, 'Uploads listados com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao listar uploads: ' . $e->getMessage());
        }
    }
    
    /**
     * Deleta um upload
     */
    public function delete($id)
    {
        try {
            $this->validateJWT();
            $this->checkPermission('uploads.delete');
            
            $restaurantId = $this->getCurrentUser()['restaurant_id'];
            
            $db = \Config\Database::connect();
            $upload = $db->table('uploads')
                ->where('id', $id)
                ->where('restaurant_id', $restaurantId)
                ->get()
                ->getRowArray();
            
            if (!$upload) {
                return $this->notFoundResponse('Upload não encontrado');
            }
            
            // Remove arquivos físicos
            $this->deletePhysicalFiles($upload);
            
            // Remove do banco
            $db->table('uploads')
                ->where('id', $id)
                ->delete();
            
            // Atualiza estatísticas de uso
            $this->updateStorageUsage($restaurantId, -$upload['file_size']);
            
            $this->logActivity('upload_delete', [
                'upload_id' => $id,
                'filename' => $upload['filename']
            ]);
            
            return $this->successResponse(null, 'Upload deletado com sucesso');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao deletar upload: ' . $e->getMessage());
        }
    }
    
    // Métodos auxiliares privados
    
    private function validateImageFile($file)
    {
        // Verifica extensão
        $extension = strtolower($file->getClientExtension());
        if (!in_array($extension, $this->allowedImageTypes)) {
            return 'Tipo de arquivo não permitido. Permitidos: ' . implode(', ', $this->allowedImageTypes);
        }
        
        // Verifica tamanho
        if ($file->getSize() > $this->maxImageSize) {
            return 'Arquivo muito grande. Tamanho máximo: ' . ($this->maxImageSize / 1024 / 1024) . 'MB';
        }
        
        // Verifica se é realmente uma imagem
        $imageInfo = getimagesize($file->getTempName());
        if (!$imageInfo) {
            return 'Arquivo não é uma imagem válida';
        }
        
        return true;
    }
    
    private function validateFile($file)
    {
        // Verifica extensão
        $extension = strtolower($file->getClientExtension());
        if (!in_array($extension, $this->allowedFileTypes)) {
            return 'Tipo de arquivo não permitido. Permitidos: ' . implode(', ', $this->allowedFileTypes);
        }
        
        // Verifica tamanho
        if ($file->getSize() > $this->maxFileSize) {
            return 'Arquivo muito grande. Tamanho máximo: ' . ($this->maxFileSize / 1024 / 1024) . 'MB';
        }
        
        return true;
    }
    
    private function generateUniqueFilename($extension)
    {
        return uniqid('upload_', true) . '.' . $extension;
    }
    
    private function optimizeImage($filePath, $extension)
    {
        try {
            switch (strtolower($extension)) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($filePath);
                    if ($image) {
                        imagejpeg($image, $filePath, 85); // Qualidade 85%
                        imagedestroy($image);
                    }
                    break;
                    
                case 'png':
                    $image = imagecreatefrompng($filePath);
                    if ($image) {
                        imagepng($image, $filePath, 6); // Compressão nível 6
                        imagedestroy($image);
                    }
                    break;
            }
        } catch (\Exception $e) {
            // Ignora erros de otimização
        }
    }
    
    private function generateThumbnails($originalPath, $uploadPath, $filename, $extension)
    {
        $thumbnails = [];
        $sizes = [
            'small' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 300, 'height' => 300],
            'large' => ['width' => 600, 'height' => 600]
        ];
        
        try {
            foreach ($sizes as $sizeName => $dimensions) {
                $thumbFilename = pathinfo($filename, PATHINFO_FILENAME) . '_' . $sizeName . '.' . $extension;
                $thumbPath = $uploadPath . $thumbFilename;
                
                if ($this->resizeImage($originalPath, $thumbPath, $dimensions['width'], $dimensions['height'])) {
                    $thumbnails[] = [
                        'size' => $sizeName,
                        'filename' => $thumbFilename,
                        'width' => $dimensions['width'],
                        'height' => $dimensions['height']
                    ];
                }
            }
        } catch (\Exception $e) {
            // Ignora erros de thumbnail
        }
        
        return $thumbnails;
    }
    
    private function resizeImage($sourcePath, $destPath, $maxWidth, $maxHeight)
    {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Calcula novas dimensões mantendo proporção
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $newWidth = (int) ($sourceWidth * $ratio);
        $newHeight = (int) ($sourceHeight * $ratio);
        
        // Cria imagem de origem
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Cria imagem de destino
        $destImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserva transparência para PNG
        if ($mimeType === 'image/png') {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            imagefill($destImage, 0, 0, $transparent);
        }
        
        // Redimensiona
        imagecopyresampled($destImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
        
        // Salva imagem
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($destImage, $destPath, 85);
                break;
            case 'image/png':
                $result = imagepng($destImage, $destPath, 6);
                break;
            case 'image/gif':
                $result = imagegif($destImage, $destPath);
                break;
        }
        
        // Limpa memória
        imagedestroy($sourceImage);
        imagedestroy($destImage);
        
        return $result;
    }
    
    private function checkStorageLimit($restaurantId, $fileSize)
    {
        // Busca plano do restaurante
        $db = \Config\Database::connect();
        $restaurant = $db->table('restaurants')
            ->select('subscription_plan')
            ->where('id', $restaurantId)
            ->get()
            ->getRowArray();
        
        if (!$restaurant) {
            return false;
        }
        
        // Define limites por plano (em bytes)
        $limits = [
            'starter' => 100 * 1024 * 1024, // 100MB
            'professional' => 1024 * 1024 * 1024, // 1GB
            'enterprise' => 10 * 1024 * 1024 * 1024 // 10GB
        ];
        
        $planLimit = $limits[$restaurant['subscription_plan']] ?? $limits['starter'];
        
        // Calcula uso atual
        $currentUsage = $db->table('uploads')
            ->selectSum('file_size')
            ->where('restaurant_id', $restaurantId)
            ->get()
            ->getRowArray()['file_size'] ?? 0;
        
        return ($currentUsage + $fileSize) <= $planLimit;
    }
    
    private function updateStorageUsage($restaurantId, $sizeChange)
    {
        // Atualiza estatísticas de uso de armazenamento
        $db = \Config\Database::connect();
        
        $usage = $db->table('restaurant_usage')
            ->where('restaurant_id', $restaurantId)
            ->where('metric', 'storage')
            ->get()
            ->getRowArray();
        
        if ($usage) {
            $db->table('restaurant_usage')
                ->where('id', $usage['id'])
                ->set('value', 'value + ' . $sizeChange, false)
                ->set('updated_at', date('Y-m-d H:i:s'))
                ->update();
        } else {
            $db->table('restaurant_usage')->insert([
                'restaurant_id' => $restaurantId,
                'metric' => 'storage',
                'value' => max(0, $sizeChange),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    private function deletePhysicalFiles($upload)
    {
        // Remove arquivo principal
        $mainFile = WRITEPATH . 'uploads/' . $upload['type'] . 's/' . $upload['restaurant_id'] . '/' . $upload['filename'];
        if (file_exists($mainFile)) {
            unlink($mainFile);
        }
        
        // Remove thumbnails se existirem
        if ($upload['thumbnails']) {
            $thumbnails = json_decode($upload['thumbnails'], true);
            foreach ($thumbnails as $thumb) {
                $thumbFile = WRITEPATH . 'uploads/' . $upload['type'] . 's/' . $upload['restaurant_id'] . '/' . $thumb['filename'];
                if (file_exists($thumbFile)) {
                    unlink($thumbFile);
                }
            }
        }
    }
}