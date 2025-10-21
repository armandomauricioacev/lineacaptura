<?php

namespace App\Services;

use App\Models\Dependencia;
use App\Models\Tramite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Servicio centralizado para operaciones de cache de dependencias y trámites,
 * encapsulando claves, TTL y funciones de carga/limpieza. Provee estadísticas
 * básicas del estado del cache.
 */
class CacheService
{
    const CACHE_TTL = 3600; // 1 hora
    const DEPENDENCIAS_KEY = 'dependencias_all';
    const TRAMITES_BY_DEPENDENCIA_KEY = 'tramites_dependencia_';

    /**
     * Obtener todas las dependencias con cache
     */
    public function getDependencias()
    {
        return Cache::remember(self::DEPENDENCIAS_KEY, self::CACHE_TTL, function () {
            Log::info('Cache miss: Cargando dependencias desde BD');
            return Dependencia::all();
        });
    }

    /**
     * Obtener trámites por dependencia con cache
     */
    public function getTramitesByDependencia($dependenciaId)
    {
        $cacheKey = self::TRAMITES_BY_DEPENDENCIA_KEY . $dependenciaId;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dependenciaId) {
            Log::info('Cache miss: Cargando trámites desde BD', ['dependencia_id' => $dependenciaId]);
            
            $dependencia = Dependencia::findOrFail($dependenciaId);
            return Tramite::where('clave_tramite', 'like', '%' . $dependencia->clave_dependencia . '%')
                ->where('tipo_agrupador', 'P')
                ->get();
        });
    }

    /**
     * Obtener dependencia específica con cache
     */
    public function getDependencia($dependenciaId)
    {
        $cacheKey = 'dependencia_' . $dependenciaId;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($dependenciaId) {
            Log::info('Cache miss: Cargando dependencia desde BD', ['dependencia_id' => $dependenciaId]);
            return Dependencia::findOrFail($dependenciaId);
        });
    }

    /**
     * Obtener trámites específicos con cache
     */
    public function getTramites(array $tramiteIds)
    {
        // Crear una copia ordenada para generar la clave de cache
        $sortedIds = $tramiteIds;
        sort($sortedIds);
        $cacheKey = 'tramites_' . md5(implode(',', $sortedIds));
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tramiteIds) {
            Log::info('Cache miss: Cargando trámites específicos desde BD', ['tramite_ids' => $tramiteIds]);
            return Tramite::whereIn('id', $tramiteIds)->get();
        });
    }

    /**
     * Limpiar cache de dependencias
     */
    public function clearDependenciasCache()
    {
        Cache::forget(self::DEPENDENCIAS_KEY);
        Log::info('Cache de dependencias limpiado');
    }

    /**
     * Limpiar cache de trámites por dependencia
     */
    public function clearTramitesCache($dependenciaId = null)
    {
        if ($dependenciaId) {
            $cacheKey = self::TRAMITES_BY_DEPENDENCIA_KEY . $dependenciaId;
            Cache::forget($cacheKey);
            Log::info('Cache de trámites limpiado', ['dependencia_id' => $dependenciaId]);
        } else {
            // Limpiar todos los caches de trámites (esto es más costoso)
            $dependencias = $this->getDependencias();
            foreach ($dependencias as $dep) {
                $cacheKey = self::TRAMITES_BY_DEPENDENCIA_KEY . $dep->id;
                Cache::forget($cacheKey);
            }
            Log::info('Todos los caches de trámites limpiados');
        }
    }

    /**
     * Limpiar todo el cache relacionado
     */
    public function clearAllCache()
    {
        $this->clearDependenciasCache();
        $this->clearTramitesCache();
        Log::info('Todo el cache del sistema limpiado');
    }

    /**
     * Obtener estadísticas del cache
     */
    public function getCacheStats()
    {
        $stats = [
            'dependencias_cached' => Cache::has(self::DEPENDENCIAS_KEY),
            'cache_driver' => config('cache.default'),
            'ttl_minutes' => self::CACHE_TTL / 60,
        ];

        // Verificar algunos caches de trámites
        $dependencias = $this->getDependencias();
        $tramitesCached = 0;
        foreach ($dependencias as $dep) {
            if (Cache::has(self::TRAMITES_BY_DEPENDENCIA_KEY . $dep->id)) {
                $tramitesCached++;
            }
        }
        
        $stats['tramites_dependencias_cached'] = $tramitesCached;
        $stats['total_dependencias'] = $dependencias->count();

        return $stats;
    }
}