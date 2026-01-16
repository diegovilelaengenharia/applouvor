# App Louvor - PIB Oliveira

Aplicativo para gestão do Ministério de Louvor da Primeira Igreja Batista em Oliveira/MG.

## Funcionalidades

### Painel Administrativo (Líder)
- **Gestão de Escalas:** Criação e edição de escalas trimestrais.
- **Gestão de Repertórios:** Montagem de listas de músicas vinculadas aos cultos.
- **Gestão de Equipe:** Controle de membros e instrumentos.

### App do Músico (Participante)
- **Minhas Escalas:** Visualização de datas e confirmação/recusa de presença.
- **Repertório:** Acesso às músicas da semana com cifras e links.
- **Sugestões:** Espaço para sugerir novas canções.

## Tecnologias
- **Backend:** PHP 7.4+ (Sem frameworks, arquitetura MVC simples)
- **Frontend:** HTML5, CSS3 (Design System próprio "Vilela Premium")
- **Banco de Dados:** MySQL
- **Infra:** Hospedagem Hostinger

## Como Rodar Localmente
1. Importe o arquivo `schema.sql` no seu banco de dados local.
2. Configure as credenciais em `includes/db.php`.
3. Inicie um servidor PHP na pasta raiz:
   ```bash
   php -S localhost:8000
   ```
4. Acesse `http://localhost:8000`.

## Deploy
Consulte o arquivo `DEPLOY_INSTRUCTIONS.md` para detalhes de como subir na Hostinger.
