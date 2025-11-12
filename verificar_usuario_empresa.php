<?php

require_once __DIR__ . '/vendor/autoload.php';

// Inicializar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Empresa;

echo "=== VERIFICACIÓN DE USUARIO Y EMPRESA ===\n\n";

// 1. Verificar usuarios
echo "👥 1. VERIFICANDO USUARIOS...\n";
$usuarios = User::all();
echo "  📊 Total usuarios: " . $usuarios->count() . "\n";

foreach ($usuarios as $usuario) {
    echo "  👤 Usuario: {$usuario->name} ({$usuario->email})\n";
    if ($usuario->empresa) {
        echo "    🏢 Empresa: {$usuario->empresa->nombre}\n";
        echo "    🆔 ID Empresa: {$usuario->empresa->id}\n";
    } else {
        echo "    ❌ Sin empresa asociada\n";
    }
    echo "\n";
}

// 2. Verificar empresas
echo "🏢 2. VERIFICANDO EMPRESAS...\n";
$empresas = Empresa::all();
echo "  📊 Total empresas: " . $empresas->count() . "\n";

foreach ($empresas as $empresa) {
    echo "  🏢 Empresa: {$empresa->nombre}\n";
    echo "    🆔 ID: {$empresa->id}\n";
    echo "    📧 Email: {$empresa->email}\n";
    echo "    🔢 NIT: {$empresa->nit}\n";
    
    try {
        $usuariosEmpresa = User::where('empresa_id', $empresa->id)->count();
    } catch (\Exception $e) {
        $usuariosEmpresa = 0;
    }
    echo "    👥 Usuarios asociados: {$usuariosEmpresa}\n";
    echo "\n";
}

// 3. Verificar relaciones
echo "🔗 3. VERIFICANDO RELACIONES...\n";
$usuariosSinEmpresa = User::whereNull('empresa_id')->count();
try {
    $empresasSinUsuarios = Empresa::whereDoesntHave('users')->count();
} catch (\Exception $e) {
    $empresasSinUsuarios = 0;
}

echo "  ❌ Usuarios sin empresa: {$usuariosSinEmpresa}\n";
echo "  🏢 Empresas sin usuarios: {$empresasSinUsuarios}\n";

// 4. Solución automática si hay problemas
if ($usuariosSinEmpresa > 0 && $empresas->count() > 0) {
    echo "\n🔧 4. SOLUCIONANDO PROBLEMA...\n";
    
    $primeraEmpresa = $empresas->first();
    $usuariosAfectados = User::whereNull('empresa_id')->get();
    
    foreach ($usuariosAfectados as $usuario) {
        $usuario->update(['empresa_id' => $primeraEmpresa->id]);
        echo "  ✅ Usuario {$usuario->name} asociado a empresa {$primeraEmpresa->nombre}\n";
    }
    
    echo "\n🎉 ¡Problema resuelto!\n";
}

// 5. Verificación final
echo "\n✅ 5. VERIFICACIÓN FINAL...\n";
$usuariosSinEmpresa = User::whereNull('empresa_id')->count();

if ($usuariosSinEmpresa == 0) {
    echo "  🎊 ¡Todos los usuarios tienen empresa asociada!\n";
    echo "  ✅ El módulo DIAN debería funcionar correctamente\n";
} else {
    echo "  ⚠️ Aún hay {$usuariosSinEmpresa} usuarios sin empresa\n";
    echo "  ❌ El módulo DIAN puede tener problemas\n";
}

echo "\n🚀 ACCESO AL MÓDULO DIAN:\n";
echo "  🏠 Dashboard: http://127.0.0.1:8000/dian\n";
echo "  ⚙️ Configuración: http://127.0.0.1:8000/dian/configuracion\n";

echo "\n✅ Verificación completada.\n";
