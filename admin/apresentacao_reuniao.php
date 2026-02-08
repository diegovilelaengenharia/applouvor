<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>App Louvor - Apresentação</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            overflow: hidden;
            height: 100vh;
        }

        .presentation-container {
            width: 100%;
            height: 100vh;
            position: relative;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 60px;
        }

        .slide.active {
            opacity: 1;
            transform: translateX(0);
            z-index: 10;
        }

        .slide.prev {
            transform: translateX(-100%);
        }

        .slide-content {
            background: white;
            border-radius: 20px;
            padding: 30px 20px;
            max-width: 100%;
            width: calc(100% - 20px);
            margin: 0 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Slide Title */
        .slide-title {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            text-align: center;
            line-height: 1.2;
        }

        .slide-subtitle {
            font-size: 1.1rem;
            color: #64748b;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .slide-text {
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        /* Pilares Grid */
        .pilares-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .pilar-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 15px;
            padding: 20px 15px;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid #e2e8f0;
        }

        .pilar-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(59, 130, 246, 0.3);
            border-color: #3b82f6;
        }

        .pilar-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .pilar-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .pilar-desc {
            font-size: 0.85rem;
            color: #64748b;
            line-height: 1.5;
        }

        /* Features List */
        .features-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-top: 20px;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .feature-item:hover {
            background: #f1f5f9;
            transform: translateX(10px);
        }

        .feature-icon {
            font-size: 1.5rem;
            color: #3b82f6;
            min-width: 35px;
        }

        .feature-text {
            flex: 1;
        }

        .feature-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 3px;
        }

        .feature-desc {
            font-size: 0.85rem;
            color: #64748b;
            line-height: 1.4;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            border-radius: 15px;
            padding: 25px 20px;
            text-align: center;
            color: white;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .stat-label {
            font-size: 1rem;
            font-weight: 600;
            opacity: 0.95;
        }

        /* Tech Stack */
        .tech-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 20px;
        }

        .tech-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }

        .tech-item:hover {
            border-color: #3b82f6;
            transform: scale(1.05);
        }

        .tech-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }

        .tech-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* Navigation */
        .nav-controls {
            position: fixed;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 100;
        }

        .nav-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #3b82f6;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }

        .nav-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .slide-counter {
            background: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.9rem;
            color: #3b82f6;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        /* Progress Bar */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 6px;
            background: linear-gradient(90deg, #1e40af 0%, #3b82f6 100%);
            transition: width 0.3s;
            z-index: 1000;
        }

        /* Highlight Box */
        .highlight-box {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
        }

        .highlight-text {
            font-size: 0.9rem;
            font-weight: 600;
            color: #92400e;
            font-style: italic;
            line-height: 1.5;
        }

        /* List Styling */
        .custom-list {
            list-style: none;
            margin-top: 15px;
        }

        .custom-list li {
            font-size: 0.9rem;
            color: #475569;
            padding: 10px 0;
            padding-left: 30px;
            position: relative;
            line-height: 1.5;
        }

        .custom-list li::before {
            content: "✨";
            position: absolute;
            left: 0;
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .slide-content {
                padding: 40px;
            }

            .slide-title {
                font-size: 2.5rem;
            }

            .pilares-grid,
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .features-list {
                grid-template-columns: 1fr;
            }

            .tech-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Cover Slide Special */
        .cover-slide .slide-content {
            text-align: center;
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.98) 100%);
        }

        .cover-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cover-title {
            font-size: 3rem;
            font-weight: 900;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
        }

        .cover-subtitle {
            font-size: 1.1rem;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .cover-date {
            font-size: 0.9rem;
            color: #94a3b8;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="progress-bar" id="progressBar"></div>
    
    <div class="presentation-container">
        <!-- Slide 1: Cover -->
        <div class="slide active">
            <div class="slide-content cover-slide">
                <div class="cover-icon">
                    <i class="fas fa-music"></i>
                </div>
                <h1 class="cover-title">APP LOUVOR</h1>
                <p class="cover-subtitle">Sua Ferramenta Completa para o Ministério de Louvor</p>
                <p class="cover-date">Reunião - 08 de Fevereiro de 2026</p>
            </div>
        </div>

        <!-- Slide 2: O que é -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">📖 O que é o App Louvor?</h2>
                <div class="highlight-box">
                    <p class="highlight-text">
                        "Uma plataforma completa que integra gestão prática, crescimento espiritual e comunicação eficiente em um único lugar."
                    </p>
                </div>
                <p class="slide-text">
                    Mais do que um sistema de escalas, é uma <strong>ferramenta de auxílio, planejamento e encorajamento</strong> para todos que servem no louvor.
                </p>
                <ul class="custom-list">
                    <li>Desenvolvido especialmente para ministérios de louvor</li>
                    <li>Pensado nas necessidades reais da nossa equipe</li>
                    <li>Gratuito e personalizado para a PIB Oliveira</li>
                    <li>Sempre evoluindo com melhorias constantes</li>
                </ul>
            </div>
        </div>

        <!-- Slide 3: Três Pilares -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">🎯 Três Pilares Fundamentais</h2>
                <div class="pilares-grid">
                    <div class="pilar-card">
                        <div class="pilar-icon">🎼</div>
                        <h3 class="pilar-title">Gestão Prática</h3>
                        <p class="pilar-desc">Escalas, repertório, membros e relatórios completos</p>
                    </div>
                    <div class="pilar-card">
                        <div class="pilar-icon">🙏</div>
                        <h3 class="pilar-title">Vida Espiritual</h3>
                        <p class="pilar-desc">Planos de leitura, diário e devocionais diários</p>
                    </div>
                    <div class="pilar-card">
                        <div class="pilar-icon">📢</div>
                        <h3 class="pilar-title">Comunicação</h3>
                        <p class="pilar-desc">Avisos, notificações e agenda integrada</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 4: Gestão Prática -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">🎼 Gestão Prática</h2>
                <p class="slide-subtitle">Organize seu ministério com eficiência profissional</p>
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="feature-text">
                            <div class="feature-title">Escalas Inteligentes</div>
                            <div class="feature-desc">Criação, confirmação automática e notificações push</div>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-music"></i></div>
                        <div class="feature-text">
                            <div class="feature-title">Repertório Completo</div>
                            <div class="feature-desc">Biblioteca com letras, tons, tags e playlists</div>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-users"></i></div>
                        <div class="feature-text">
                            <div class="feature-title">Gestão de Membros</div>
                            <div class="feature-desc">Perfis, funções, disponibilidade e estatísticas</div>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                        <div class="feature-text">
                            <div class="feature-title">Relatórios</div>
                            <div class="feature-desc">Boletins estatísticos e indicadores de desempenho</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 5: Vida Espiritual -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">🙏 Vida Espiritual</h2>
                <p class="slide-subtitle">Cresça em intimidade com Deus enquanto serve</p>
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-book-open"></i></div>
                        <div class="feature-text">
                            <div class="feature-title">Planos de Leitura Bíblica</div>
                            <div class="feature-desc">3 planos: Bíblia em 1 Ano, Novo Testamento, Salmos & Provérbios</div>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-pen-fancy"></i></div>
                        <div class="feature-text">
                            <div class="feature-title">Diário Espiritual</div>
                            <div class="feature-desc">Reflexões pessoais com áudio e imagens</div>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-dove"></i></div>
                        <div class="feature-text">
                            <div class="feature-title">Devocionais</div>
                            <div class="feature-desc">Conteúdo inspiracional diário sobre louvor</div>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-praying-hands"></i></div>
                        <div class="feature-text">
                            <div class="feature-title">Pedidos de Oração</div>
                            <div class="feature-desc">Compartilhamento e comunhão através da intercessão</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 6: Números do Desenvolvimento -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">💻 Números do Desenvolvimento</h2>
                <p class="slide-subtitle">Estatísticas impressionantes</p>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">50.757</div>
                        <div class="stat-label">Linhas de Código</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">~600h</div>
                        <div class="stat-label">Horas de Trabalho</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">40+</div>
                        <div class="stat-label">Funcionalidades</div>
                    </div>
                </div>
                <div class="highlight-box" style="margin-top: 40px;">
                    <p class="highlight-text">
                        Equivalente a 3-4 meses de trabalho em tempo integral ou 6-8 meses em tempo parcial
                    </p>
                </div>
            </div>
        </div>

        <!-- Slide 7: Tecnologias -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">🔧 Tecnologias Utilizadas</h2>
                <div class="tech-grid">
                    <div class="tech-item">
                        <div class="tech-icon" style="color: #777bb4;">
                            <i class="fab fa-php"></i>
                        </div>
                        <div class="tech-name">PHP 7.4+</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-icon" style="color: #00758f;">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="tech-name">MySQL</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-icon" style="color: #f7df1e;">
                            <i class="fab fa-js"></i>
                        </div>
                        <div class="tech-name">JavaScript</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-icon" style="color: #264de4;">
                            <i class="fab fa-css3-alt"></i>
                        </div>
                        <div class="tech-name">CSS3</div>
                    </div>
                </div>
                <ul class="custom-list" style="margin-top: 40px;">
                    <li><strong>PWA:</strong> Instalável como app nativo</li>
                    <li><strong>Web Push:</strong> Notificações mesmo com app fechado</li>
                    <li><strong>Gravação de Áudio:</strong> API nativa do navegador</li>
                    <li><strong>Exportação Word:</strong> Geração de documentos</li>
                </ul>
            </div>
        </div>

        <!-- Slide 8: Diferenciais -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">🚀 Diferenciais do App Louvor</h2>
                <p class="slide-subtitle">O que torna este app único</p>
                <ul class="custom-list">
                    <li><strong>Integração Total:</strong> Gestão + Espiritualidade + Comunicação em um só lugar</li>
                    <li><strong>Foco no Crescimento:</strong> Não apenas organiza, mas edifica espiritualmente</li>
                    <li><strong>Desenvolvido por quem entende:</strong> Criado pensando nas necessidades reais</li>
                    <li><strong>Gratuito e Personalizado:</strong> Feito com amor para nossa igreja</li>
                    <li><strong>Sempre Evoluindo:</strong> Melhorias constantes baseadas no uso real</li>
                    <li><strong>Dados Seguros:</strong> Hospedado de forma segura e confiável</li>
                </ul>
            </div>
        </div>

        <!-- Slide 9: Visão e Propósito -->
        <div class="slide">
            <div class="slide-content">
                <h2 class="slide-title">🎯 Visão e Propósito</h2>
                <div class="highlight-box">
                    <p class="highlight-text">
                        "Criar uma ferramenta que não apenas organize o ministério, mas que também encoraje cada membro a crescer espiritualmente enquanto serve."
                    </p>
                </div>
                <div class="features-list" style="margin-top: 40px;">
                    <div class="feature-item">
                        <div class="feature-icon">✨</div>
                        <div class="feature-text">
                            <div class="feature-title">Facilitar</div>
                            <div class="feature-desc">A gestão do ministério de louvor</div>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🙏</div>
                        <div class="feature-text">
                            <div class="feature-title">Encorajar</div>
                            <div class="feature-desc">A vida devocional de cada membro</div>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">🤝</div>
                        <div class="feature-text">
                            <div class="feature-title">Fortalecer</div>
                            <div class="feature-desc">A comunicação e união da equipe</div>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">📈</div>
                        <div class="feature-text">
                            <div class="feature-title">Promover</div>
                            <div class="feature-desc">Excelência no serviço ao Senhor</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 10: Encerramento -->
        <div class="slide">
            <div class="slide-content cover-slide">
                <div class="cover-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h2 class="slide-title" style="font-size: 3.5rem;">Obrigado!</h2>
                <div class="highlight-box" style="margin-top: 40px;">
                    <p class="highlight-text">
                        Que este app seja uma bênção para nosso ministério e glorifique o nome de Jesus!
                    </p>
                </div>
                <p class="slide-text" style="text-align: center; margin-top: 40px; font-size: 1.5rem;">
                    <strong>🎵 APP LOUVOR 🎵</strong><br>
                    <span style="color: #94a3b8;">Organizando o ministério, edificando vidas</span>
                </p>
            </div>
        </div>
    </div>

    <div class="nav-controls">
        <button class="nav-btn" id="prevBtn" onclick="prevSlide()">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="slide-counter">
            <span id="currentSlide">1</span> / <span id="totalSlides">10</span>
        </div>
        <button class="nav-btn" id="nextBtn" onclick="nextSlide()">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <script>
        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;
        const progressBar = document.getElementById('progressBar');
        const currentSlideEl = document.getElementById('currentSlide');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        function updateSlide() {
            slides.forEach((slide, index) => {
                slide.classList.remove('active', 'prev');
                if (index === currentSlideIndex) {
                    slide.classList.add('active');
                } else if (index < currentSlideIndex) {
                    slide.classList.add('prev');
                }
            });

            currentSlideEl.textContent = currentSlideIndex + 1;
            progressBar.style.width = ((currentSlideIndex + 1) / totalSlides * 100) + '%';

            prevBtn.disabled = currentSlideIndex === 0;
            nextBtn.disabled = currentSlideIndex === totalSlides - 1;
        }

        function nextSlide() {
            if (currentSlideIndex < totalSlides - 1) {
                currentSlideIndex++;
                updateSlide();
            }
        }

        function prevSlide() {
            if (currentSlideIndex > 0) {
                currentSlideIndex--;
                updateSlide();
            }
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ') {
                e.preventDefault();
                nextSlide();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                prevSlide();
            } else if (e.key === 'Home') {
                e.preventDefault();
                currentSlideIndex = 0;
                updateSlide();
            } else if (e.key === 'End') {
                e.preventDefault();
                currentSlideIndex = totalSlides - 1;
                updateSlide();
            }
        });

        // Touch/Swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });

        document.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });

        function handleSwipe() {
            if (touchEndX < touchStartX - 50) {
                nextSlide();
            }
            if (touchEndX > touchStartX + 50) {
                prevSlide();
            }
        }

        // Initialize
        document.getElementById('totalSlides').textContent = totalSlides;
        updateSlide();
    </script>
</body>
</html>
