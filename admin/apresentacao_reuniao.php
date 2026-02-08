<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apresentação App Louvor - PIB Oliveira</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            /* Cores Neutras */
            --white: #FFFFFF;
            --gray-50: #F5F5F7;
            --gray-100: #E8E8ED;
            --gray-400: #86868B;
            --gray-900: #1D1D1F;
            --black: #000000;
            
            /* Acentos Vivos */
            --blue: #0071E3;
            --green: #30D158;
            --orange: #FF9500;
            --purple: #BF5AF2;
            
            /* Gradientes */
            --gradient-blue: linear-gradient(135deg, #0071E3, #005BB5);
            --gradient-green: linear-gradient(135deg, #30D158, #28A745);
            --gradient-orange: linear-gradient(135deg, #FF9500, #E68600);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--black);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: var(--gray-900);
            -webkit-font-smoothing: antialiased;
        }

        .presentation-wrapper {
            width: 100vw;
            height: 100vh;
            background: var(--white);
            position: relative;
            overflow: hidden;
        }

        /* Slides */
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 80px 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
        }

        .slide.active {
            opacity: 1;
            transform: translateY(0);
            z-index: 10;
        }

        .slide-content {
            width: 100%;
            max-width: 1200px;
            text-align: center;
        }

        /* Typography */
        h1 {
            font-size: 6rem;
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
            color: var(--gray-900);
            margin-bottom: 24px;
        }

        h2 {
            font-size: 4.5rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -0.01em;
            color: var(--gray-900);
            margin-bottom: 32px;
        }

        h3 {
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 16px;
        }

        p {
            font-size: 1.75rem;
            line-height: 1.6;
            color: var(--gray-400);
            font-weight: 400;
        }

        .subtitle {
            font-size: 2.5rem;
            color: var(--gray-400);
            font-weight: 500;
            margin-bottom: 40px;
        }

        /* Accent Text */
        .accent-blue { color: var(--blue); }
        .accent-green { color: var(--green); }
        .accent-orange { color: var(--orange); }
        .accent-purple { color: var(--purple); }

        /* Cover Slide */
        .cover-slide {
            background: var(--gray-900);
            color: var(--white);
        }

        .cover-slide h1 {
            color: var(--white);
            font-size: 6rem;
        }

        .cover-slide .subtitle {
            color: var(--gray-400);
        }

        .cover-icon {
            font-size: 5rem;
            margin-bottom: 40px;
            background: var(--gradient-blue);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Grid Layouts */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-top: 60px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-top: 60px;
        }

        /* Cards */
        .card {
            background: var(--white);
            border: 2px solid var(--gray-100);
            border-radius: 24px;
            padding: 48px 40px;
            text-align: left;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-blue);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            border-color: var(--blue);
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 24px;
            display: block;
        }

        .card-icon.blue { color: var(--blue); }
        .card-icon.green { color: var(--green); }
        .card-icon.orange { color: var(--orange); }
        .card-icon.purple { color: var(--purple); }

        .card h3 {
            font-size: 1.75rem;
            margin-bottom: 16px;
        }

        .card p {
            font-size: 1.5rem;
            line-height: 1.6;
        }

        /* Feature List */
        .feature-list {
            list-style: none;
            text-align: left;
            margin: 40px auto;
            max-width: 900px;
        }

        .feature-list li {
            font-size: 1.75rem;
            padding: 24px 0;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .feature-list .icon.blue { background: rgba(0, 113, 227, 0.1); color: var(--blue); }
        .feature-list .icon.green { background: rgba(48, 209, 88, 0.1); color: var(--green); }
        .feature-list .icon.orange { background: rgba(255, 149, 0, 0.1); color: var(--orange); }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
            margin-top: 60px;
        }

        .stat-box {
            text-align: center;
        }

        .stat-number {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 16px;
            background: var(--gradient-blue);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 1.25rem;
            color: var(--gray-400);
            font-weight: 500;
        }

        /* Comparison Table */
        .comparison-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: separate;
            border-spacing: 0 12px;
        }

        .comparison-table th {
            font-size: 1.125rem;
            font-weight: 600;
            padding: 16px;
            text-align: left;
            color: var(--gray-400);
        }

        .comparison-table td {
            font-size: 1.25rem;
            padding: 20px 16px;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-100);
            border-bottom: 1px solid var(--gray-100);
        }

        .comparison-table td:first-child {
            border-left: 1px solid var(--gray-100);
            border-radius: 12px 0 0 12px;
            font-weight: 600;
        }

        .comparison-table td:last-child {
            border-right: 1px solid var(--gray-100);
            border-radius: 0 12px 12px 0;
        }

        .comparison-table .highlight {
            background: rgba(0, 113, 227, 0.05);
            color: var(--blue);
            font-weight: 600;
        }

        /* Progress Bar */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            background: var(--gradient-blue);
            z-index: 1000;
            transition: width 0.3s ease;
        }

        /* Slide Counter */
        .slide-counter {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background: var(--gray-900);
            color: var(--white);
            padding: 12px 24px;
            border-radius: 24px;
            font-size: 1rem;
            font-weight: 600;
            z-index: 1000;
        }

        /* Navigation */
        .nav-controls {
            position: fixed;
            bottom: 40px;
            left: 40px;
            display: flex;
            gap: 16px;
            z-index: 1000;
        }

        .nav-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--gray-900);
            color: var(--white);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: var(--blue);
            transform: scale(1.1);
        }

        /* Pill Badge */
        .pill {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .pill.blue {
            background: rgba(0, 113, 227, 0.1);
            color: var(--blue);
        }

        .pill.green {
            background: rgba(48, 209, 88, 0.1);
            color: var(--green);
        }

        .pill.orange {
            background: rgba(255, 149, 0, 0.1);
            color: var(--orange);
        }

        /* Quote */
        .quote-box {
            background: var(--gray-50);
            border-left: 4px solid var(--blue);
            padding: 40px;
            border-radius: 16px;
            margin: 40px 0;
            text-align: left;
        }

        .quote-text {
            font-size: 2.5rem;
            line-height: 1.6;
            color: var(--gray-900);
            font-style: normal;
            margin-bottom: 20px;
        }

        .quote-ref {
            font-size: 1.25rem;
            color: var(--blue);
            font-weight: 600;
        }

        /* Divider Slide */
        .divider-slide {
            background: var(--gradient-blue);
            color: var(--white);
        }

        .divider-slide h1,
        .divider-slide h2 {
            color: var(--white);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeInUp 0.6s ease forwards;
        }

        /* Utility */
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .mt-4 { margin-top: 2rem; }
        .mb-4 { margin-bottom: 2rem; }

        /* Print Styles for PDF */
        @media print {
            body {
                background: white;
            }
            
            .slide {
                page-break-after: always;
                page-break-inside: avoid;
                position: relative;
                opacity: 1 !important;
                transform: none !important;
                display: flex !important;
                width: 100vw;
                height: 100vh;
                margin: 0;
                padding: 80px 120px;
            }
            
            .slide.active {
                page-break-before: avoid;
            }
            
            .progress-bar,
            .slide-counter,
            .nav-controls {
                display: none !important;
            }
            
            @page {
                size: landscape;
                margin: 0;
            }
        }

    </style>
</head>
<body>

    <div class="presentation-wrapper">
        <div class="progress-bar" id="progressBar"></div>
        <div class="slide-counter" id="slideCounter">1 / 26</div>

        <!-- PARTE 1: ALINHAMENTO ESPIRITUAL -->

        <!-- Slide 1: Capa -->
        <div class="slide active cover-slide">
            <div class="slide-content">
                <img src="../assets/img/logo_pib_apresentacao.png" alt="PIB Oliveira" style="width: 180px; margin-bottom: 40px; filter: brightness(0) invert(1);">
                <h1>Reunião de Alinhamento<br>e Planejamento 2026</h1>
                <p class="subtitle">PIB Oliveira • Ministério de Música</p>
                <p style="color: var(--gray-400); font-size: 1.25rem; margin-top: 60px;">08 de Fevereiro de 2026</p>
                <button onclick="downloadPDF()" id="downloadBtn" style="
                    margin-top: 60px;
                    padding: 16px 40px;
                    background: var(--gradient-blue);
                    color: white;
                    border: none;
                    border-radius: 12px;
                    font-size: 1.125rem;
                    font-weight: 600;
                    cursor: pointer;
                    display: inline-flex;
                    align-items: center;
                    gap: 12px;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 12px rgba(0, 113, 227, 0.3);
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0, 113, 227, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0, 113, 227, 0.3)'" class="no-print">
                    <i class="fas fa-download"></i>
                    <span id="btnText">Baixar Apresentação em PDF</span>
                </button>
            </div>
        </div>

        <!-- Slide 2: Agenda -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill blue">AGENDA</span>
                <h2>O que veremos hoje</h2>
                <div class="grid-2">
                    <div class="card">
                        <i class="fas fa-bible card-icon blue"></i>
                        <h3>Fundamentos Espirituais</h3>
                        <p>Reflexão bíblica sobre o fogo no altar e nosso papel como habitação e holocausto</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-bullseye card-icon green"></i>
                        <h3>Propostas 2026</h3>
                        <p>Visão, capacitação, medidas práticas e estruturação do repertório</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-mobile-alt card-icon orange"></i>
                        <h3>App Louvor</h3>
                        <p>Nossa ferramenta completa de gestão e crescimento espiritual</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-rocket card-icon purple"></i>
                        <h3>Próximos Passos</h3>
                        <p>Como participar e agenda de eventos futuros</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 3: Palavra Inicial -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill blue">PALAVRA INICIAL</span>
                <h2>O Fogo no Altar</h2>
                <div class="quote-box">
                    <p class="quote-text">
                        "Mantenha-se aceso o fogo no altar; não deve ser apagado. <span style="color: #DC2626; font-weight: 700;">Toda manhã</span> o sacerdote acrescentará lenha... Mantenha-se o fogo <span style="color: #DC2626; font-weight: 700;">continuamente</span> aceso no altar; não deve ser apagado."
                    </p>
                    <p class="quote-ref">LEVÍTICO 6:12-13</p>
                </div>
            </div>
        </div>

        <!-- Slide 4: Tópico 01 -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill green">TÓPICO 01</span>
                <h2>Somos a <span class="accent-blue">HABITAÇÃO</span> de Deus</h2>
                <div class="quote-box">
                    <p class="quote-text">
                        "Acaso não sabem que o corpo de vocês é santuário do Espírito Santo que habita em vocês, que lhes foi dado por Deus, e que vocês não são de vocês mesmos? Vocês foram comprados por alto preço. Portanto, glorifiquem a Deus com o seu próprio corpo"
                    </p>
                    <p class="quote-ref">1 CORÍNTIOS 6:19-20</p>
                </div>
            </div>
        </div>

        <!-- Slide 5: Tópico 02 -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill orange">TÓPICO 02</span>
                <h2>Somos o <span class="accent-green">HOLOCAUSTO</span> oferecido</h2>
                <div class="quote-box">
                    <p class="quote-text">
                        "Portanto, irmãos, rogo pelas misericórdias de Deus que se (você mesmo) ofereçam em SACRIFÍCIO VIVO, SANTO E AGRADÁVEL a Deus; este é o culto racional de vocês"
                    </p>
                    <p class="quote-ref">ROMANOS 12:1</p>
                </div>
            </div>
        </div>

        <!-- Slide 5.5: Música - O Fogo Arderá (Parte 1) -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill orange">MÚSICA</span>
                <h2 style="margin-bottom: 20px;">O Fogo Arderá</h2>
                <p style="font-size: 1.25rem; color: var(--gray-400); margin-bottom: 60px;">Alexsander Lucio • 2024</p>
                <div style="text-align: center; font-size: 2rem; line-height: 2; color: var(--gray-900);">
                    <p style="margin-bottom: 48px;">
                        Teu fogo arde em mim<br>
                        E muda o meu viver<br>
                        Sou Teu templo, sou Teu altar<br>
                        Sacrifício vivo eu quero oferecer
                    </p>
                    <p style="margin-bottom: 48px;">
                        Que o nosso louvor<br>
                        Não seja um momento<br>
                        Lágrimas vazias<br>
                        Sem avivamento
                    </p>
                </div>
            </div>
        </div>

        <!-- Slide 5.6: Música - O Fogo Arderá (Parte 2) -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill orange">MÚSICA</span>
                <h2 style="margin-bottom: 20px;">O Fogo Arderá</h2>
                <p style="font-size: 1.25rem; color: var(--gray-400); margin-bottom: 60px;">Alexsander Lucio • 2024</p>
                <div style="text-align: center; font-size: 2rem; line-height: 2; color: var(--gray-900);">
                    <p style="margin-bottom: 48px;">
                        Que o nosso amor<br>
                        Não seja fingido<br>
                        Honrado com os lábios<br>
                        Nos corações esquecido
                    </p>
                    <p style="margin-bottom: 48px;">
                        Não vale a pena só me emocionar<br>
                        E buscar as canções mais lindas pra cantar<br>
                        Se eu não for uma canção que alegra a Ti<br>
                        Se o meu coração não queimar por Ti
                    </p>
                </div>
            </div>
        </div>

        <!-- Slide 5.7: Música - O Fogo Arderá (Refrão) -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill orange">MÚSICA</span>
                <h2 style="margin-bottom: 20px;">O Fogo Arderá</h2>
                <p style="font-size: 1.25rem; color: var(--gray-400); margin-bottom: 80px;">Alexsander Lucio • 2024</p>
                <div style="text-align: center; font-size: 2.5rem; line-height: 2; color: var(--orange); font-weight: 700;">
                    <p>
                        O fogo arderá sobre o altar<br>
                        Continuamente sobre o altar<br>
                        Não se apagará
                    </p>
                </div>
            </div>
        </div>

        <!-- Slide 5.8: Música - O Fogo Arderá (Parte 3) -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill orange">MÚSICA</span>
                <h2 style="margin-bottom: 20px;">O Fogo Arderá</h2>
                <p style="font-size: 1.25rem; color: var(--gray-400); margin-bottom: 60px;">Alexsander Lucio • 2024</p>
                <div style="text-align: center; font-size: 2rem; line-height: 2; color: var(--gray-900);">
                    <p style="margin-bottom: 48px;">
                        Pois de que vale ter as poesias mais lindas<br>
                        As harmonias mais brilhantes<br>
                        Se não há verdade em nós?
                    </p>
                    <p style="margin-bottom: 48px;">
                        Pois de que vale ter tudo e não ter nada<br>
                        Te dar meus lábios e não minha alma<br>
                        Eu quero me entregar a Ti<br>
                        Entregar minha vida como adoração
                    </p>
                </div>
            </div>
        </div>


        <!-- Slide 6: Propostas 2026 -->
        <div class="slide divider-slide">
            <div class="slide-content">
                <h1>Propostas 2026</h1>
                <p class="subtitle">Fortalecimento Espiritual e Estruturação</p>
            </div>
        </div>

        <!-- Slide 7: Visão e Capacitação -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill blue">VISÃO E CAPACITAÇÃO</span>
                <h2>Fortalecimento Espiritual</h2>
                <div class="grid-2" style="margin-top: 60px;">
                    <div class="card">
                        <i class="fas fa-bullseye card-icon blue"></i>
                        <h3 style="font-size: 2rem; margin-bottom: 24px;">O Propósito do Ministro</h3>
                        <p style="font-size: 1.5rem; line-height: 1.8;">Entender nosso papel além da música. Temos responsabilidade naquilo que estamos falando.</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-book-bible card-icon green"></i>
                        <h3 style="font-size: 2rem; margin-bottom: 24px;">Fundamentos Bíblicos</h3>
                        <p style="font-size: 1.5rem; line-height: 1.8;">Adoração, Serviço, Excelência<br>Escolha consciente de músicas<br>Tradição e novidades</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 8: Medidas Práticas -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill green">MEDIDAS PRÁTICAS</span>
                <h2>Como vamos crescer</h2>
                <div class="grid-2">
                    <div class="card">
                        <i class="fas fa-sync-alt card-icon blue"></i>
                        <h3>Rotação de Duplas</h3>
                        <p>Dinamismo e integração na equipe</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-bible card-icon green"></i>
                        <h3>Disciplinas Espirituais</h3>
                        <p>Estímulo ao Jejum e Devocionais Semanais</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-church card-icon orange"></i>
                        <h3>Assiduidade</h3>
                        <p>Envolvimento real com a igreja local</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-users card-icon purple"></i>
                        <h3>Comunhão</h3>
                        <p>Encontrão Semestral e Tópico aberto</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 9: Repertório -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill orange">REPERTÓRIO</span>
                <h2>Estruturação Intencional</h2>
                <p class="subtitle">Escolhas baseadas em propósito e técnica</p>
                <ul class="feature-list">
                    <li>
                        <span class="icon blue"><i class="fas fa-music"></i></span>
                        <div><strong>02 Canções Livres</strong></div>
                    </li>
                    <li>
                        <span class="icon green"><i class="fas fa-graduation-cap"></i></span>
                        <div><strong>01 Canção de Ensino</strong> (Congregação)</div>
                    </li>
                    <li>
                        <span class="icon orange"><i class="fas fa-star"></i></span>
                        <div><strong>01 Canção Temática</strong> (Específica/Desafio)</div>
                    </li>
                </ul>
                <p style="margin-top: 40px; color: var(--gray-400);">Reuniões Bimestrais de Alinhamento</p>
            </div>
        </div>

        <!-- PARTE 2: APP LOUVOR -->

        <!-- Slide 10: App Louvor Intro -->
        <div class="slide divider-slide" style="background: var(--gradient-green);">
            <div class="slide-content">
                <i class="fas fa-mobile-alt" style="font-size: 6rem; margin-bottom: 40px;"></i>
                <h1>App Louvor</h1>
                <p class="subtitle">A Ferramenta do Ministério</p>
            </div>
        </div>

        <!-- Slide 11: O que é -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill blue">O QUE É?</span>
                <h2>Plataforma Completa de Gestão</h2>
                <p class="subtitle">Desenvolvida especialmente para o nosso ministério</p>
                <div class="grid-3" style="margin-top: 60px;">
                    <div class="card">
                        <i class="fas fa-check-circle card-icon green"></i>
                        <h3>Progressive Web App</h3>
                        <p>Instalável no celular sem loja de apps</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-palette card-icon blue"></i>
                        <h3>Interface Moderna</h3>
                        <p>Design profissional e intuitivo</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-cog card-icon orange"></i>
                        <h3>100% Personalizado</h3>
                        <p>Feito sob medida para nós</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 12: 3 Pilares -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill green">3 PILARES</span>
                <h2>Fundamentos do Sistema</h2>
                <div class="grid-3" style="margin-top: 60px;">
                    <div class="card">
                        <i class="fas fa-tasks card-icon blue"></i>
                        <h3>Gestão Prática</h3>
                        <p>Escalas, repertório, membros e histórico completo</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-pray card-icon green"></i>
                        <h3>Vida Espiritual</h3>
                        <p>Planos de leitura, diário e devocionais semanais</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-bullhorn card-icon orange"></i>
                        <h3>Comunicação</h3>
                        <p>Avisos, agenda e notificações em tempo real</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 13: Funcionalidades Líder -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill blue">PARA LÍDERES</span>
                <h2>Painel Administrativo</h2>
                <ul class="feature-list">
                    <li>
                        <span class="icon blue"><i class="fas fa-calendar-alt"></i></span>
                        <div><strong>Gestão de Escalas:</strong> Criação trimestral e controle de confirmações</div>
                    </li>
                    <li>
                        <span class="icon green"><i class="fas fa-music"></i></span>
                        <div><strong>Gestão de Repertório:</strong> Músicas com cifras, tons e links</div>
                    </li>
                    <li>
                        <span class="icon orange"><i class="fas fa-users"></i></span>
                        <div><strong>Gestão de Equipe:</strong> Membros, instrumentos e permissões</div>
                    </li>
                    <li>
                        <span class="icon blue"><i class="fas fa-chart-bar"></i></span>
                        <div><strong>Relatórios:</strong> Análise completa de participação e dados</div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Slide 14: Funcionalidades Músicos -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill green">PARA MÚSICOS</span>
                <h2>App do Participante</h2>
                <ul class="feature-list">
                    <li>
                        <span class="icon blue"><i class="fas fa-calendar-check"></i></span>
                        <div><strong>Minhas Escalas:</strong> Visualização e confirmação de presença</div>
                    </li>
                    <li>
                        <span class="icon green"><i class="fas fa-guitar"></i></span>
                        <div><strong>Repertório:</strong> Letras, cifras e links para estudo</div>
                    </li>
                    <li>
                        <span class="icon orange"><i class="fas fa-lightbulb"></i></span>
                        <div><strong>Sugestões:</strong> Propor novas músicas para o ministério</div>
                    </li>
                    <li>
                        <span class="icon blue"><i class="fas fa-book-open"></i></span>
                        <div><strong>Planos de Leitura:</strong> Bíblia em 1 ano, NT, Salmos</div>
                    </li>
                    <li>
                        <span class="icon green"><i class="fas fa-pen"></i></span>
                        <div><strong>Diário Espiritual:</strong> Reflexões com áudio e imagens</div>
                    </li>
                </ul>
            </div>
        </div>



        <!-- Slide 16: Estatísticas -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill blue">BASTIDORES</span>
                <h2>Números Impressionantes</h2>
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-number">50k+</div>
                        <div class="stat-label">Linhas de Código</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" style="background: var(--gradient-green); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">600h</div>
                        <div class="stat-label">Horas de Trabalho</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number" style="background: var(--gradient-orange); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">25+</div>
                        <div class="stat-label">Páginas Admin</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Personalização</div>
                    </div>
                </div>
                <p style="margin-top: 60px; font-size: 1.5rem; color: var(--gray-400);">Feito com excelência para Deus</p>
            </div>
        </div>

        <!-- Slide 17: Impacto -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill green">IMPACTO</span>
                <h2>Resultados Alcançados</h2>
                <div class="grid-2" style="margin-top: 60px;">
                    <div class="card">
                        <i class="fas fa-clock card-icon blue"></i>
                        <h3>Economia de Tempo</h3>
                        <p style="font-size: 2rem; color: var(--blue); font-weight: 700; margin: 20px 0;">85%</p>
                        <p>De 2-3 horas para 15 minutos na montagem de escalas</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-dollar-sign card-icon green"></i>
                        <h3>Custo-Benefício</h3>
                        <p style="font-size: 2rem; color: var(--green); font-weight: 700; margin: 20px 0;">90%</p>
                        <p>Economia vs. alternativas do mercado (R$ 30 vs R$ 300+)</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-chart-line card-icon orange"></i>
                        <h3>Engajamento</h3>
                        <p>Confirmações mais rápidas, maior participação e feedback constante</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-heart card-icon purple"></i>
                        <h3>Crescimento Espiritual</h3>
                        <p>Acompanhamento de leitura bíblica e diário espiritual ativo</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 18: Comparação -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill orange">COMPARAÇÃO</span>
                <h2>App Louvor vs. Alternativas</h2>
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th>Característica</th>
                            <th>App Louvor</th>
                            <th>Planning Center</th>
                            <th>Planilhas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Custo Mensal</td>
                            <td class="highlight">R$ 30</td>
                            <td>R$ 350</td>
                            <td>Grátis</td>
                        </tr>
                        <tr>
                            <td>Personalização</td>
                            <td class="highlight">100%</td>
                            <td>20%</td>
                            <td>50%</td>
                        </tr>
                        <tr>
                            <td>Vida Espiritual</td>
                            <td class="highlight">✅</td>
                            <td>❌</td>
                            <td>❌</td>
                        </tr>
                        <tr>
                            <td>Suporte BR</td>
                            <td class="highlight">✅</td>
                            <td>❌</td>
                            <td>N/A</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Slide 19: Como Acessar -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill blue">COMO ACESSAR</span>
                <h2>Comece Agora</h2>
                <div class="grid-2" style="margin-top: 60px;">
                    <div class="card">
                        <i class="fas fa-globe card-icon blue"></i>
                        <h3>Acesso Web</h3>
                        <p style="font-size: 1.75rem; color: var(--blue); font-weight: 600; margin: 20px 0;">vilela.eng.br/applouvor</p>
                        <p>Acesse de qualquer navegador</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-mobile-screen card-icon green"></i>
                        <h3>Instalação PWA</h3>
                        <p><strong>Android:</strong> Menu → Adicionar à tela inicial</p>
                        <p style="margin-top: 12px;"><strong>iOS:</strong> Compartilhar → Adicionar à Tela Inicial</p>
                    </div>
                </div>
                <div style="margin-top: 60px; padding: 40px; background: var(--gray-50); border-radius: 16px;">
                    <p style="font-size: 1.25rem; color: var(--gray-900);"><i class="fas fa-qrcode" style="color: var(--blue); margin-right: 12px;"></i>Escaneie o QR Code na próxima tela para acesso rápido</p>
                </div>
            </div>
        </div>

        <!-- Slide 20: QR Code -->
        <div class="slide">
            <div class="slide-content">
                <h2>Acesso Rápido</h2>
                <div style="margin-top: 60px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=https://vilela.eng.br/applouvor" 
                         alt="QR Code App Louvor" 
                         style="border: 8px solid var(--gray-900); border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.2);">
                    <p style="margin-top: 40px; font-size: 1.5rem; color: var(--gray-400);">Aponte a câmera do celular para acessar</p>
                    <p style="margin-top: 16px; font-size: 2rem; color: var(--blue); font-weight: 700;">vilela.eng.br/applouvor</p>
                </div>
            </div>
        </div>

        <!-- Slide 21: Próximos Passos -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill green">PRÓXIMOS PASSOS</span>
                <h2>Agenda 2026</h2>
                <ul class="feature-list">
                    <li>
                        <span class="icon blue"><i class="fas fa-calendar"></i></span>
                        <div><strong>Março:</strong> Reunião Bimestral de Repertório</div>
                    </li>
                    <li>
                        <span class="icon green"><i class="fas fa-users"></i></span>
                        <div><strong>Junho:</strong> Encontrão Semestral do Ministério</div>
                    </li>
                    <li>
                        <span class="icon orange"><i class="fas fa-graduation-cap"></i></span>
                        <div><strong>Próxima Semana:</strong> Treinamento App Louvor</div>
                    </li>
                </ul>
                <div style="margin-top: 60px; padding: 40px; background: rgba(0, 113, 227, 0.05); border: 2px solid var(--blue); border-radius: 16px;">
                    <p style="font-size: 1.5rem; color: var(--gray-900);"><i class="fas fa-question-circle" style="color: var(--blue); margin-right: 12px;"></i><strong>Dúvidas ou Sugestões?</strong> Fale com a liderança!</p>
                </div>
            </div>
        </div>

        <!-- Slide 22: Como Participar -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill orange">CHAMADA PARA AÇÃO</span>
                <h2>Como Participar?</h2>
                <div class="grid-2" style="margin-top: 60px;">
                    <div class="card">
                        <i class="fas fa-user-check card-icon blue"></i>
                        <h3>Para Líderes</h3>
                        <p>✓ Acessar painel administrativo<br>
                           ✓ Explorar funcionalidades<br>
                           ✓ Criar primeira escala<br>
                           ✓ Convidar membros</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-music card-icon green"></i>
                        <h3>Para Músicos</h3>
                        <p>✓ Fazer login no app<br>
                           ✓ Instalar PWA no celular<br>
                           ✓ Confirmar escalas<br>
                           ✓ Iniciar leitura bíblica</p>
                    </div>
                </div>
                <p style="margin-top: 60px; font-size: 1.75rem; color: var(--gray-900); font-weight: 600;">
                    <span class="accent-blue">Compromisso</span> e <span class="accent-green">Dedicação</span> são essenciais
                </p>
            </div>
        </div>

        <!-- Slide 23: Roadmap -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill purple">FUTURO</span>
                <h2>Roadmap 2026</h2>
                <div class="grid-2" style="margin-top: 60px;">
                    <div class="card">
                        <i class="fas fa-mobile-alt card-icon blue"></i>
                        <h3>Q2 - Abr/Jun</h3>
                        <p>App mobile nativo (Android/iOS) e sistema de partituras digitais</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-brain card-icon green"></i>
                        <h3>Q3 - Jul/Set</h3>
                        <p>IA para sugestões inteligentes e análise preditiva de escalas</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-gamepad card-icon orange"></i>
                        <h3>Q3 - Jul/Set</h3>
                        <p>Gamificação avançada e sistema de mentoria</p>
                    </div>
                    <div class="card">
                        <i class="fas fa-share-nodes card-icon purple"></i>
                        <h3>Q4 - Out/Dez</h3>
                        <p>Integração com redes sociais e transmissão ao vivo</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 24: Agradecimentos -->
        <div class="slide">
            <div class="slide-content">
                <span class="pill blue">AGRADECIMENTOS</span>
                <h2>Obrigado!</h2>
                <div style="margin-top: 60px;">
                    <p style="font-size: 1.75rem; color: var(--gray-900); margin-bottom: 40px;">
                        <strong>Desenvolvedor:</strong> Diego T. N. Vilela<br>
                        <span style="color: var(--gray-400); font-size: 1.25rem;">Engenheiro de Software</span>
                    </p>
                    <p style="font-size: 1.5rem; color: var(--gray-400); margin-bottom: 60px;">
                        <i class="fab fa-whatsapp" style="color: var(--green);"></i> (35) 98452-9577
                    </p>
                    <div class="grid-3">
                        <div>
                            <i class="fas fa-users" style="font-size: 3rem; color: var(--blue); margin-bottom: 16px;"></i>
                            <p>Liderança do Ministério</p>
                        </div>
                        <div>
                            <i class="fas fa-heart" style="font-size: 3rem; color: var(--green); margin-bottom: 16px;"></i>
                            <p>Membros Testadores</p>
                        </div>
                        <div>
                            <i class="fas fa-church" style="font-size: 3rem; color: var(--orange); margin-bottom: 16px;"></i>
                            <p>PIB Oliveira</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 25: Final -->
        <div class="slide cover-slide">
            <div class="slide-content">
                <i class="fas fa-rocket" style="font-size: 6rem; margin-bottom: 40px; background: var(--gradient-green); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                <h1>Vamos Juntos!</h1>
                <p class="subtitle">Fazer de 2026 um ano incrível para o Ministério</p>
                <p style="margin-top: 60px; font-size: 1.5rem; color: var(--gray-400); font-style: italic;">
                    "A excelência honra a Deus e inspira as pessoas."
                </p>
            </div>
        </div>

    </div>

    <!-- Controls -->
    <div class="nav-controls">
        <button class="nav-btn" onclick="prevSlide()"><i class="fas fa-chevron-left"></i></button>
        <button class="nav-btn" onclick="nextSlide()"><i class="fas fa-chevron-right"></i></button>
    </div>

    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const totalSlides = slides.length;
        const progressBar = document.getElementById('progressBar');
        const slideCounter = document.getElementById('slideCounter');

        function updateSlide() {
            slides.forEach((slide, index) => {
                slide.classList.remove('active');
                if (index === currentSlide) {
                    slide.classList.add('active');
                }
            });
            
            const progress = ((currentSlide + 1) / totalSlides) * 100;
            progressBar.style.width = progress + '%';
            slideCounter.textContent = `${currentSlide + 1} / ${totalSlides}`;
        }

        function nextSlide() {
            if (currentSlide < totalSlides - 1) {
                currentSlide++;
                updateSlide();
            }
        }

        function prevSlide() {
            if (currentSlide > 0) {
                currentSlide--;
                updateSlide();
            }
        }

        // Keyboard support
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === ' ') {
                e.preventDefault();
                nextSlide();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                prevSlide();
            } else if (e.key === 'Home') {
                e.preventDefault();
                currentSlide = 0;
                updateSlide();
            } else if (e.key === 'End') {
                e.preventDefault();
                currentSlide = totalSlides - 1;
                updateSlide();
            }
        });
        
        updateSlide();
        
        // Função para download PDF
        async function downloadPDF() {
            const btn = document.getElementById('downloadBtn');
            const btnText = document.getElementById('btnText');
            const originalText = btnText.textContent;
            
            // Feedback visual
            btn.disabled = true;
            btnText.textContent = 'Gerando PDF...';
            btn.style.opacity = '0.7';
            btn.style.cursor = 'wait';
            
            try {
                // Configurações do PDF
                const opt = {
                    margin: 0,
                    filename: 'Apresentacao_PIB_Oliveira_2026.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { 
                        scale: 2,
                        useCORS: true,
                        logging: false,
                        windowWidth: 1920,
                        windowHeight: 1080
                    },
                    jsPDF: { 
                        unit: 'px', 
                        format: [1920, 1080], 
                        orientation: 'landscape',
                        compress: true
                    },
                    pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
                };
                
                // Preparar slides para PDF
                const slides = document.querySelectorAll('.slide');
                const wrapper = document.querySelector('.presentation-wrapper');
                
                // Mostrar todos os slides temporariamente
                slides.forEach(slide => {
                    slide.style.opacity = '1';
                    slide.style.transform = 'none';
                    slide.style.position = 'relative';
                });
                
                // Gerar PDF
                await html2pdf().set(opt).from(wrapper).save();
                
                // Restaurar estado original
                slides.forEach((slide, index) => {
                    if (index !== currentSlide) {
                        slide.style.opacity = '0';
                        slide.style.transform = 'translateY(30px)';
                        slide.style.position = 'absolute';
                    }
                });
                
                // Feedback de sucesso
                btnText.textContent = 'PDF Baixado!';
                setTimeout(() => {
                    btnText.textContent = originalText;
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                }, 2000);
                
            } catch (error) {
                console.error('Erro ao gerar PDF:', error);
                btnText.textContent = 'Erro ao gerar PDF';
                setTimeout(() => {
                    btnText.textContent = originalText;
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                }, 2000);
            }
        }
    </script>
</body>
</html>
