<?php

namespace App\Helpers;

class VersionHelper
{
    /**
     * Obtiene la versión completa de la aplicación
     */
    public static function getVersion(): string
    {
        return config('app_version.version', '1.0.0');
    }
    
    /**
     * Obtiene el nombre de la versión
     */
    public static function getVersionName(): string
    {
        return config('app_version.version_name', 'Sistema Base');
    }
    
    /**
     * Obtiene la fecha de lanzamiento
     */
    public static function getReleaseDate(): string
    {
        return config('app_version.release_date', date('Y-m-d'));
    }
    
    /**
     * Obtiene el build number
     */
    public static function getBuild(): string
    {
        return config('app_version.build', 'dev');
    }
    
    /**
     * Obtiene la versión completa con build
     */
    public static function getFullVersion(): string
    {
        $version = self::getVersion();
        $build = self::getBuild();
        
        return $build !== 'dev' ? "{$version} (build {$build})" : $version;
    }
    
    /**
     * Verifica si es una versión de pre-release
     */
    public static function isPreRelease(): bool
    {
        $version = self::getVersion();
        return str_contains($version, '-') && 
               (str_contains($version, 'alpha') || 
                str_contains($version, 'beta') || 
                str_contains($version, 'rc'));
    }
    
    /**
     * Obtiene el tipo de pre-release
     */
    public static function getPreReleaseType(): ?string
    {
        if (!self::isPreRelease()) {
            return null;
        }
        
        $version = self::getVersion();
        if (str_contains($version, 'alpha')) return 'alpha';
        if (str_contains($version, 'beta')) return 'beta';
        if (str_contains($version, 'rc')) return 'rc';
        
        return null;
    }
    
    /**
     * Obtiene las funcionalidades de la versión actual
     */
    public static function getFeatures(): array
    {
        return config('app_version.features', []);
    }
    
    /**
     * Obtiene información del desarrollador
     */
    public static function getDeveloper(): array
    {
        return config('app_version.developer', []);
    }
    
    /**
     * Obtiene los requerimientos del sistema
     */
    public static function getRequirements(): array
    {
        return config('app_version.requirements', []);
    }
    
    /**
     * Obtiene el changelog de la versión actual
     */
    public static function getCurrentChangelog(): array
    {
        $version = self::getVersion();
        $changelog = config('app_version.changelog', []);
        
        return $changelog[$version] ?? [];
    }
    
    /**
     * Genera un string formateado para mostrar en footer
     */
    public static function getFooterVersion(): string
    {
        $version = self::getVersion();
        $name = self::getVersionName();
        $date = self::getReleaseDate();
        
        return "v{$version} - {$name} ({$date})";
    }
    
    /**
     * Genera información completa para página "Acerca de"
     */
    public static function getAboutInfo(): array
    {
        return [
            'version' => self::getVersion(),
            'version_name' => self::getVersionName(),
            'full_version' => self::getFullVersion(),
            'release_date' => self::getReleaseDate(),
            'is_pre_release' => self::isPreRelease(),
            'pre_release_type' => self::getPreReleaseType(),
            'features' => self::getFeatures(),
            'developer' => self::getDeveloper(),
            'requirements' => self::getRequirements(),
            'changelog' => self::getCurrentChangelog(),
        ];
    }
}
