<?php

namespace App\Domains\Core\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable; // Importar Throwable

class FileStorageService
{
    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'text/plain',
    ];

    /**
     * Verifica se o arquivo possui um tipo MIME permitido
     *
     * @param UploadedFile $file
     * @param array|null $allowedTypes Tipos específicos para esta validação (opcional)
     * @return bool
     */
    public function isAllowedFile(UploadedFile $file, ?array $allowedTypes = null): bool
    {
        $types = $allowedTypes ?? $this->allowedMimeTypes;
        return in_array($file->getMimeType(), $types);
    }

    /**
     * Armazena um arquivo no disco especificado e retorna o caminho.
     *
     * @param UploadedFile $file
     * @param string $path Diretório onde o arquivo será armazenado
     * @param string $disk Disco de armazenamento (default: 'public')
     * @param bool $preserveFileName Preservar nome original ou gerar UUID
     * @param array|null $allowedTypes Tipos de arquivo permitidos (opcional)
     * @return string Caminho relativo do arquivo armazenado
     * @throws \InvalidArgumentException Se o tipo de arquivo não for permitido
     */
    public function store(
        UploadedFile $file, 
        string $path = 'uploads', 
        string $disk = 'public', 
        bool $preserveFileName = false,
        ?array $allowedTypes = null
    ): string {
        // Validar tipo do arquivo
        if (!$this->isAllowedFile($file, $allowedTypes)) {
            $mimeType = $file->getMimeType();
            Log::warning('Tentativa de upload de arquivo com tipo não permitido', [
                'mime_type' => $mimeType,
                'original_name' => $file->getClientOriginalName()
            ]);
            throw new \InvalidArgumentException("Tipo de arquivo não permitido: {$mimeType}");
        }
        
        // Sanitizar nome do arquivo se estiver preservando o nome original
        if ($preserveFileName) {
            $sanitizedName = $this->sanitizeFileName($file->getClientOriginalName());
            return Storage::disk($disk)->putFileAs($path, $file, $sanitizedName);
        }
        
        // Usar UUID para nome seguro
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return Storage::disk($disk)->putFileAs($path, $file, $fileName);
    }
    
    /**
     * Sanitiza um nome de arquivo para evitar problemas de segurança
     *
     * @param string $fileName
     * @return string
     */
    protected function sanitizeFileName(string $fileName): string
    {
        // Remover caracteres potencialmente perigosos
        $fileName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $fileName);
        
        // Evitar nomes de arquivos que começam com ponto (ocultos)
        if (str_starts_with($fileName, '.')) {
            $fileName = 'file_' . $fileName;
        }
        
        return $fileName;
    }
    
    /**
     * Armazena um arquivo temporário que expira após um tempo determinado.
     *
     * @param UploadedFile $file
     * @param int $expiryMinutes Minutos até expirar (default: 60)
     * @return string URL temporária para o arquivo
     */
    public function storeTemporary(UploadedFile $file, int $expiryMinutes = 60): string
    {
        $path = 'temp/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        Storage::put($path, file_get_contents($file->getRealPath()));
        
        // Define expiração usando o sistema de filas
        dispatch(function () use ($path) {
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        })->delay(now()->addMinutes($expiryMinutes));
        
        return Storage::url($path);
    }
    
    /**
     * Remove um arquivo do armazenamento.
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public function delete(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($path);
    }
    
    /**
     * Verifica se um arquivo existe.
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public function exists(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists($path);
    }
    
    /**
     * Gera uma URL temporária para um arquivo privado.
     *
     * @param string $path
     * @param \DateTimeInterface $expiration
     * @param string $disk
     * @return string
     */
    public function temporaryUrl(string $path, \DateTimeInterface $expiration, string $disk = 's3'): string
    {
        return Storage::disk($disk)->temporaryUrl($path, $expiration);
    }

    /**
     * Define os tipos MIME permitidos.
     *
     * @param array $allowedMimeTypes
     * @return $this
     */
    public function setAllowedMimeTypes(array $allowedMimeTypes): self
    {
        $this->allowedMimeTypes = $allowedMimeTypes;
        return $this;
    }
}