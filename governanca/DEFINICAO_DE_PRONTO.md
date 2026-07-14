# Definição de Pronto — ⛪ applouvor

Uma fatia só está PRONTA quando:

1. **Funciona por execução** — app 8020 exercitado (ou PWA aberto), não só teste.
2. **Suíte verde** — `.\governanca\pronto.ps1 -Full` sem falha nova.
3. **Sem dado do ministério no git** — `louvor.db` e derivados ficam fora (o gate barra).
4. **CHANGELOG.md** ganhou a linha; decisão grande virou ADR.
5. **Deploy consciente** — se a fatia exige publicar, o push foi decisão explícita
   (Actions → Hostinger), com o site conferido DEPOIS no ar.
