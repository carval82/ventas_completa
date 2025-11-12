@extends('layouts.app')

@section('title', 'Acerca de')

@section('content')
<div class="about-container">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <div class="hero-icon">
                <i class="fas fa-heart"></i>
            </div>
            <h1 class="hero-title">Sistema de Ventas</h1>
            <p class="hero-subtitle">Desarrollado con amor y dedicación para facilitar la gestión empresarial</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content-grid">
        <!-- Información del Desarrollador -->
        <div class="info-card developer-card">
            <div class="card-icon">
                <i class="fas fa-code"></i>
            </div>
            <h2>Desarrollador</h2>
            <div class="developer-info">
                <h3>Luis Carlos Correa Arrieta</h3>
                <p class="title">Tecnólogo en Análisis y Desarrollo de Software</p>
                <p class="description">
                    Apasionado por crear soluciones tecnológicas que simplifiquen los procesos empresariales 
                    y mejoren la productividad de las organizaciones.
                </p>
            </div>
        </div>

        <!-- Agradecimientos Especiales -->
        <div class="info-card gratitude-card">
            <div class="card-icon">
                <i class="fas fa-heart"></i>
            </div>
            <h2>Agradecimientos Especiales</h2>
            <div class="gratitude-content">
                <div class="gratitude-item wife">
                    <div class="gratitude-icon">
                        <i class="fas fa-ring"></i>
                    </div>
                    <div class="gratitude-text">
                        <h4>Sandra Miladys Mora Benítez</h4>
                        <p>Mi compañera de vida, por su amor incondicional, paciencia y apoyo constante en cada proyecto. Tu fortaleza y comprensión han sido fundamentales para alcanzar este logro.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dedicatoria a las Hijas -->
        <div class="info-card daughters-card">
            <div class="card-icon">
                <i class="fas fa-baby"></i>
            </div>
            <h2>Dedicado con Amor</h2>
            <div class="daughters-content">
                <p class="dedication-intro">A mis hermosas hijas, mi mayor inspiración y motivación:</p>
                <div class="daughters-list">
                    <div class="daughter-item">
                        <div class="daughter-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4>Valery Correa</h4>
                        <p>Mi princesa valiente</p>
                    </div>
                    <div class="daughter-item">
                        <div class="daughter-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4>Ana Lía Correa</h4>
                        <p>Mi dulce compañía</p>
                    </div>
                    <div class="daughter-item">
                        <div class="daughter-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4>Liana Correa</h4>
                        <p>Mi pequeña luz</p>
                    </div>
                </div>
                <p class="daughters-message">
                    Que este trabajo sea un ejemplo de que con dedicación y esfuerzo se pueden alcanzar los sueños. 
                    Ustedes son mi mayor tesoro y la razón de cada línea de código.
                </p>
            </div>
        </div>

        <!-- Dedicatoria Especial a las Mujeres que me Inspiran -->
        <div class="info-card memorial-card">
            <div class="card-icon">
                <i class="fas fa-heart"></i>
            </div>
            <h2>Mujeres que me Inspiran</h2>
            <div class="memorial-content">
                <div class="women-inspiration">
                    <!-- Abuela -->
                    <div class="inspiration-woman">
                        <div class="woman-photo">
                            <div class="photo-placeholder">
                                <i class="fas fa-crown"></i>
                            </div>
                        </div>
                        <div class="woman-info">
                            <h3>Bertha Arrieta</h3>
                            <p class="woman-subtitle">Mi querida abuela de 90 años ✨</p>
                            <div class="woman-message">
                                <p>
                                    A sus 90 años sigues siendo mi mayor inspiración. Con tu sabiduría, 
                                    amor incondicional y ejemplo de vida me has enseñado a nunca rendirme 
                                    y a perseguir mis sueños con determinación.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Madre -->
                    <div class="inspiration-woman">
                        <div class="woman-photo">
                            <div class="photo-placeholder">
                                <i class="fas fa-heart"></i>
                            </div>
                        </div>
                        <div class="woman-info">
                            <h3>Ingrid Lucía Correa Arrieta</h3>
                            <p class="woman-subtitle">Mi querida madre 💝</p>
                            <div class="woman-message">
                                <p>
                                    Tu amor, apoyo y sacrificios han sido fundamentales en mi formación. 
                                    Eres un ejemplo de fortaleza, dedicación y amor incondicional que 
                                    me motiva cada día a ser mejor persona y profesional.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="combined-message">
                    <p>
                        Estas dos mujeres extraordinarias han sido mis pilares fundamentales. 
                        Su fortaleza, experiencia y consejos han sido esenciales en mi crecimiento 
                        personal y profesional. Este sistema es un tributo a sus vidas extraordinarias 
                        y al poder transformador del amor familiar que me han brindado.
                    </p>
                    <p>
                        Gracias por ser mis guías, mis ejemplos y mi fuente de inspiración para 
                        concluir estas aplicaciones y seguir adelante con cada proyecto.
                    </p>
                    <p class="memorial-quote">
                        "Las mujeres que me inspiran a todo: mi abuela y mi madre" ❤️
                    </p>
                </div>
            </div>
        </div>

        <!-- Información Técnica -->
        <div class="info-card tech-card">
            <div class="card-icon">
                <i class="fas fa-cogs"></i>
            </div>
            <h2>Información Técnica</h2>
            <div class="tech-content">
                <!-- Información de Versión -->
                <div class="version-section">
                    <h4>Versión del Sistema</h4>
                    <div class="version-display">
                        <div class="version-badge {{ \App\Helpers\VersionHelper::isPreRelease() ? 'beta' : 'stable' }}">
                            <span class="version-number">v{{ \App\Helpers\VersionHelper::getVersion() }}</span>
                            <span class="version-name">{{ \App\Helpers\VersionHelper::getVersionName() }}</span>
                        </div>
                        <div class="version-details">
                            <p><strong>Fecha de lanzamiento:</strong> {{ \Carbon\Carbon::parse(\App\Helpers\VersionHelper::getReleaseDate())->format('d/m/Y') }}</p>
                            @if(\App\Helpers\VersionHelper::isPreRelease())
                                <p class="beta-notice">
                                    <i class="fas fa-flask"></i>
                                    <strong>Versión {{ strtoupper(\App\Helpers\VersionHelper::getPreReleaseType()) }}:</strong> 
                                    En fase de pruebas y desarrollo
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="tech-stack">
                    <h4>Tecnologías Utilizadas</h4>
                    <div class="tech-items">
                        <span class="tech-item">Laravel {{ app()->version() }}</span>
                        <span class="tech-item">PHP {{ PHP_VERSION }}</span>
                        <span class="tech-item">MySQL</span>
                        <span class="tech-item">Bootstrap</span>
                        <span class="tech-item">JavaScript</span>
                        <span class="tech-item">HTML5</span>
                        <span class="tech-item">CSS3</span>
                    </div>
                </div>
                
                <div class="features">
                    <h4>Funcionalidades de esta Versión</h4>
                    <ul>
                        @foreach(\App\Helpers\VersionHelper::getFeatures() as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- Derechos de Autor -->
        <div class="info-card copyright-card">
            <div class="card-icon">
                <i class="fas fa-copyright"></i>
            </div>
            <h2>Derechos de Autor</h2>
            <div class="copyright-content">
                <div class="copyright-notice">
                    <p><strong>© {{ date('Y') }} Luis Carlos Correa Arrieta</strong></p>
                    <p>Tecnólogo en Análisis y Desarrollo de Software</p>
                </div>
                <div class="license-info">
                    <p>
                        Este sistema ha sido desarrollado con dedicación y profesionalismo. 
                        Todos los derechos están reservados al autor.
                    </p>
                    <p class="version-info">
                        <strong>Versión:</strong> {{ \App\Helpers\VersionHelper::getFullVersion() }}<br>
                        <strong>Fecha de desarrollo:</strong> {{ \App\Helpers\VersionHelper::getReleaseDate() }}<br>
                        <strong>Contacto:</strong> {{ \App\Helpers\VersionHelper::getDeveloper()['email'] ?? 'pcapacho24@gmail.com' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Message -->
    <div class="footer-message">
        <div class="footer-content">
            <i class="fas fa-heart"></i>
            <p>Desarrollado con amor, dedicación y el apoyo incondicional de mi familia</p>
            <p class="footer-signature">- Luis Carlos Correa Arrieta</p>
        </div>
    </div>
</div>

<style>
    .about-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Hero Section */
    .hero-section {
        text-align: center;
        padding: 60px 0;
        background: var(--primary-gradient);
        border-radius: var(--border-radius);
        margin-bottom: 40px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
    }

    @keyframes rotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .hero-icon {
        font-size: 4rem;
        margin-bottom: 20px;
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    .hero-title {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 16px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }

    .hero-subtitle {
        font-size: 1.2rem;
        opacity: 0.9;
        font-weight: 400;
    }

    /* Content Grid */
    .content-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }

    .info-card {
        background: var(--card-bg);
        backdrop-filter: blur(20px);
        border-radius: var(--border-radius);
        padding: 30px;
        box-shadow: var(--shadow-lg);
        transition: var(--transition);
        border: 1px solid var(--border-color);
    }

    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-xl);
    }

    .card-icon {
        font-size: 2.5rem;
        margin-bottom: 20px;
        color: var(--primary-color);
    }

    .info-card h2 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--text-color);
    }

    /* Developer Card */
    .developer-info h3 {
        font-size: 1.4rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 8px;
    }

    .developer-info .title {
        font-size: 1rem;
        color: var(--text-muted);
        font-weight: 500;
        margin-bottom: 16px;
    }

    .developer-info .description {
        line-height: 1.6;
        color: var(--text-color);
    }

    /* Gratitude Card */
    .gratitude-item {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        padding: 20px;
        background: rgba(102, 126, 234, 0.05);
        border-radius: var(--border-radius-sm);
        border-left: 4px solid var(--primary-color);
    }

    .gratitude-icon {
        font-size: 2rem;
        color: #e91e63;
        flex-shrink: 0;
    }

    .gratitude-text h4 {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 8px;
    }

    .gratitude-text p {
        line-height: 1.6;
        color: var(--text-color);
    }

    /* Daughters Card */
    .dedication-intro {
        font-size: 1.1rem;
        margin-bottom: 24px;
        color: var(--text-color);
        font-weight: 500;
    }

    .daughters-list {
        display: grid;
        gap: 16px;
        margin-bottom: 24px;
    }

    .daughter-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        background: rgba(102, 126, 234, 0.05);
        border-radius: var(--border-radius-sm);
    }

    .daughter-icon {
        font-size: 1.5rem;
        color: #ffd700;
    }

    .daughter-item h4 {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--text-color);
        margin: 0;
    }

    .daughter-item p {
        font-size: 0.9rem;
        color: var(--text-muted);
        margin: 0;
    }

    .daughters-message {
        font-style: italic;
        line-height: 1.6;
        color: var(--text-color);
        padding: 20px;
        background: rgba(102, 126, 234, 0.05);
        border-radius: var(--border-radius-sm);
        border-left: 4px solid var(--primary-color);
    }

    /* Memorial Card - Women Inspiration */
    .memorial-content {
        text-align: center;
    }

    .women-inspiration {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
    }

    .inspiration-woman {
        background: rgba(102, 126, 234, 0.05);
        border-radius: var(--border-radius-sm);
        padding: 24px;
        text-align: center;
        border: 1px solid var(--border-color);
        transition: var(--transition);
    }

    .inspiration-woman:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-md);
    }

    .woman-photo {
        margin-bottom: 20px;
    }

    .photo-placeholder {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: var(--primary-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        font-size: 2.5rem;
        color: white;
        box-shadow: var(--shadow-lg);
    }

    .woman-info h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--text-color);
        margin-bottom: 8px;
    }

    .woman-subtitle {
        font-size: 1rem;
        color: var(--text-muted);
        margin-bottom: 16px;
        font-weight: 500;
    }

    .woman-message p {
        line-height: 1.6;
        color: var(--text-color);
        font-size: 0.95rem;
    }

    .combined-message {
        background: rgba(102, 126, 234, 0.05);
        border-radius: var(--border-radius-sm);
        padding: 24px;
        border-left: 4px solid var(--primary-color);
        text-align: left;
    }

    .combined-message p {
        line-height: 1.7;
        margin-bottom: 16px;
        color: var(--text-color);
    }

    .memorial-quote {
        font-style: italic;
        font-size: 1.1rem;
        color: var(--primary-color);
        font-weight: 600;
        text-align: center;
        margin-bottom: 0;
    }

    /* Tech Card */
    .tech-content h4 {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 16px;
        color: var(--text-color);
    }

    /* Version Section */
    .version-section {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border-color);
    }

    .version-display {
        text-align: center;
    }

    .version-badge {
        display: inline-block;
        padding: 12px 24px;
        border-radius: 25px;
        margin-bottom: 16px;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
    }

    .version-badge.stable {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .version-badge.beta {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: white;
        animation: pulse 2s ease-in-out infinite;
    }

    .version-number {
        font-size: 1.4rem;
        font-weight: 700;
        display: block;
    }

    .version-name {
        font-size: 0.9rem;
        opacity: 0.9;
        display: block;
        margin-top: 4px;
    }

    .version-details {
        text-align: left;
        max-width: 400px;
        margin: 0 auto;
    }

    .version-details p {
        margin-bottom: 8px;
        color: var(--text-color);
    }

    .beta-notice {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid #ffc107;
        border-radius: var(--border-radius-sm);
        padding: 12px;
        color: #856404;
        font-size: 0.9rem;
    }

    .beta-notice i {
        margin-right: 8px;
        color: #ffc107;
    }

    .tech-items {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 24px;
    }

    .tech-item {
        background: var(--primary-gradient);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .features ul {
        list-style: none;
        padding: 0;
    }

    .features li {
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color);
    }

    .features li:before {
        content: '✓';
        color: var(--primary-color);
        font-weight: bold;
        margin-right: 10px;
    }

    /* Copyright Card */
    .copyright-notice {
        text-align: center;
        margin-bottom: 20px;
    }

    .copyright-notice p:first-child {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .license-info {
        line-height: 1.6;
        color: var(--text-color);
    }

    .version-info {
        margin-top: 16px;
        padding: 16px;
        background: rgba(102, 126, 234, 0.05);
        border-radius: var(--border-radius-sm);
        font-size: 0.9rem;
    }

    /* Footer Message */
    .footer-message {
        text-align: center;
        padding: 40px;
        background: var(--card-bg);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-lg);
        margin-bottom: 40px;
    }

    .footer-content i {
        font-size: 2rem;
        color: #e91e63;
        margin-bottom: 16px;
    }

    .footer-content p {
        font-size: 1.1rem;
        color: var(--text-color);
        margin-bottom: 8px;
    }

    .footer-signature {
        font-style: italic;
        color: var(--text-muted);
        font-weight: 500;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .content-grid {
            grid-template-columns: 1fr;
        }

        .hero-title {
            font-size: 2rem;
        }

        .hero-subtitle {
            font-size: 1rem;
        }

        .gratitude-item {
            flex-direction: column;
            text-align: center;
        }
    }
</style>
@endsection
