<?php

namespace App\Services;

use App\Enums\TipoFoto;
use App\Models\Foto;
use App\Models\Ocorrencia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

/**
 * Comprime e armazena fotos no disco privado (storage/app/private).
 * Nunca grava na pasta pública: as fotos são servidas por controller com Policy.
 */
class FotoService
{
    public function salvar(UploadedFile $arquivo, Ocorrencia $ocorrencia, TipoFoto $tipo, User $user): Foto
    {
        $largura = (int) config('sistema.fotos.largura_max', 1600);
        $qualidade = (int) config('sistema.fotos.qualidade', 80);

        $imagem = ImageManager::usingDriver(GdDriver::class)
            ->decodePath($arquivo->getRealPath())   // já aplica a orientação EXIF
            ->scaleDown($largura, $largura);

        $conteudo = $imagem->encode(new JpegEncoder(quality: $qualidade))->toString();

        $path = sprintf('ocorrencias/%s/%s.jpg', now()->format('Y/m'), Str::uuid());
        Storage::disk('local')->put($path, $conteudo);

        // Se a transação for desfeita, remove o arquivo para não deixar órfão
        DB::afterRollBack(fn () => Storage::disk('local')->delete($path));

        return $ocorrencia->fotos()->create([
            'tipo' => $tipo,
            'path' => $path,
            'tamanho' => strlen($conteudo),
            'user_id' => $user->id,
        ]);
    }

    /** @param iterable<UploadedFile> $arquivos */
    public function salvarVarias(iterable $arquivos, Ocorrencia $ocorrencia, TipoFoto $tipo, User $user): void
    {
        foreach ($arquivos as $arquivo) {
            $this->salvar($arquivo, $ocorrencia, $tipo, $user);
        }
    }

    public function remover(Foto $foto): void
    {
        $path = $foto->path;
        $foto->delete();
        DB::afterCommit(fn () => Storage::disk('local')->delete($path));
    }
}
