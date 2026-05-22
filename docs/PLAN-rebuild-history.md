# Plano de Implementação: Reconstrução da Inteligência de Repertório

Este plano estabelece a estratégia para consertar os erros fatais do PHP e reconstruir a página de **Inteligência de Repertório** (`admin/historico.php`), que atualmente se encontra quebrada devido a variáveis não inicializadas e queries ausentes.

---

## 🔍 Contexto do Problema

A tela `admin/historico.php` apresenta erros fatais do tipo `Warning: Undefined variable $kpiCards` e `Warning: foreach() argument must be of type array|object, null given`. Isso ocorre porque as variáveis `$kpiCards`, `$musicasXRay`, `$topTags` e `$usoTons` são utilizadas nas abas e grids da interface, mas **não foram declaradas ou populadas** com dados vindos do banco de dados na seção de controle no início do arquivo PHP.

---

## ❓ Perguntas de Descoberta (Socratic Gate)

> [!IMPORTANT]
> Por favor, responda a estas 3 perguntas estratégicas para direcionarmos a implementação:
> 
> 1. **Preservação de Design:** Deseja que mantenhamos a estrutura visual atual baseada no padrão *Sacred Minimalist* (com as abas "Visão Geral", "Raio-X", "Tags & Tons" e "Laboratório") e apenas conserte as variáveis e consultas SQL, ou gostaria de evoluir a identidade visual da página para algo ainda mais premium?
> 2. **Novas Métricas:** Além dos KPIs planejados (como músicas ativas, taxa de uso, alta rotatividade e geladeira), há alguma outra métrica ou indicador que seria enriquecedor adicionar à Visão Geral (ex: média de BPM do período, quantidade de cultos realizados)?
> 3. **Novos Recursos no Laboratório:** No laboratório de escolha inteligente, a busca atualmente aceita filtros de Tom, Tag e se a música não foi tocada em 90 dias. Gostaria de adicionar filtros por Artista ou ordenação por relevância/data de última execução?

---

## 🛠️ Alterações Propostas

### [Backend - Banco de Dados e Queries]

Consolidação de consultas SQL no topo de `admin/historico.php` para extrair todas as informações de estatísticas e inteligência de repertório diretamente das tabelas `songs`, `schedule_songs` e `schedules`.

#### [MODIFY] [historico.php](file:///c:/Users/diego/Meu%20Drive/02.%20Trabalho/04.%20Vilela.eng%20Site/APP%20Louvor%20(COM%20STICH)/admin/historico.php)

- **Popular `$musicasXRay`:** Implementar query robusta com `DATEDIFF` e `LEFT JOIN` para calcular o número de execuções das músicas no período selecionado (ex: 90 dias) e histórico total, além de calcular os dias desde a última execução.
- **Construir `$kpiCards`:** Computar dinamicamente no PHP os quatro blocos de KPIs de saúde:
  - *Músicas Ativas:* Quantidade de músicas tocadas $\ge 1$ vez no período.
  - *Taxa de Uso:* Porcentagem do acervo total que foi ativado no período.
  - *Alta Rotatividade:* Músicas tocadas $\ge 3$ vezes no período (potencial de desgaste).
  - *Na Geladeira:* Músicas do repertório sem tocar há mais de 90 dias (mas já tocadas anteriormente).
- **Popular `$topTags`:** Implementar query para rankear as tags (estilos) mais executadas no período ativo.
- **Popular `$usoTons`:** Implementar query para agrupar e rankear a distribuição de tons no período ativo.
- **Tratamento de Erros:** Adicionar blocos `try/catch` para garantir resiliência caso o banco de dados esteja vazio ou tabelas complementares não existam.

---

## 🧪 Plano de Verificação

### Testes Manuais
1. Acessar a página `admin/historico.php` e validar se os erros de variáveis indefinidas desapareceram.
2. Clicar nas abas "Visão Geral", "Raio-X", "Tags & Tons" e "Laboratório" e certificar-se de que a transição e a renderização do layout bento grid ocorrem de forma fluida.
3. Testar a barra de busca instantânea e os filtros por status (Geladeira, Esquecida, Alta Rot.) na aba Raio-X.
4. Executar uma combinação de filtros no Laboratório e certificar-se de que as sugestões de repertório retornam os registros apropriados do banco.
