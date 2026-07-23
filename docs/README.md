# Documentacao - Atribuicao Inteligente

Esta pasta centraliza a documentacao tecnica, historica e operacional do plugin
**Atribuicao Inteligente**.

## Documentos

- [HISTORICO.md](HISTORICO.md): resumo executivo do historico e estado atual do
  plugin.
- [RELATORIO_TECNICO.md](RELATORIO_TECNICO.md): relatorio tecnico consolidado,
  com arquitetura, banco, funcionalidades, testes, erros, riscos e proximos
  passos.
- [PRD.md](PRD.md): documento de requisitos de produto, com problema,
  objetivos, escopo, requisitos funcionais, requisitos nao funcionais,
  criterios de aceite, riscos e evolucoes futuras.
- [PRD.docx](PRD.docx): versao Word do PRD para leitura, revisao ou
  compartilhamento fora do repositorio.
- [../../../../docs/licoes-aprendidas/README.md](../../../../docs/licoes-aprendidas/README.md):
  licoes aprendidas globais do workspace, registradas em arquivos proprios no
  formato recomendado pela documentacao do projeto e pela skill GLPI Plugins
  GLPI 10.
- [ANTIPADROES.md](ANTIPADROES.md): relatorio historico especifico do plugin
  com antipadroes observados, evitados ou ainda pendentes de endurecimento.
  Antipadroes reutilizaveis devem ser registrados na base central
  `docs/padroes/antipadroes-glpi-plugins.md`.
- [CHECKLIST_DOCUMENTACAO_GLPI10.md](CHECKLIST_DOCUMENTACAO_GLPI10.md):
  checklist de aderencia documental, limitacoes, rollback e proximos passos.

## Uso recomendado

Antes de evoluir o plugin:

1. Ler o relatorio tecnico.
2. Conferir licoes aprendidas relacionadas ao tema na pasta global
   `docs/licoes-aprendidas/`.
3. Verificar antipadroes aplicaveis na base central `docs/padroes/`.
4. Atualizar esta documentacao sempre que uma correcao relevante gerar novo
   conhecimento reutilizavel.
