# Checklist documental GLPI 10 - Atribuicao Inteligente

Este checklist consolida a aderencia documental do plugin as recomendacoes da
skill GLPI Plugins GLPI 10.

## Objetivo

Registrar o que foi documentado sobre o plugin, os riscos conhecidos, as licoes
aprendidas, os antipadroes e os procedimentos operacionais, sem alterar codigo de
execucao.

## Arquivos criados

- `docs/HISTORICO.md`
- `docs/RELATORIO_TECNICO.md`
- `docs/ANTIPADROES.md`
- `docs/CHECKLIST_DOCUMENTACAO_GLPI10.md`

Arquivos globais de licoes aprendidas criados no workspace:

- `../../../docs/licoes-aprendidas/LA-001-dropdown-nativo-interceptado.md`
- `../../../docs/licoes-aprendidas/LA-002-permissao-plugin-nao-sincronizada.md`
- `../../../docs/licoes-aprendidas/LA-003-url-menu-duplicando-glpi.md`
- `../../../docs/licoes-aprendidas/LA-004-redirect-acao-caminho-duplicado.md`
- `../../../docs/licoes-aprendidas/LA-005-entidades-html-exibidas-como-texto.md`
- `../../../docs/licoes-aprendidas/LA-006-categorias-ativas-por-padrao.md`
- `../../../docs/licoes-aprendidas/LA-007-metodo-inexistente-glpi-10025.md`
- `../../../docs/licoes-aprendidas/LA-008-install-idempotente-duplicate-primary-key.md`
- `../../../docs/licoes-aprendidas/LA-009-controle-por-entidade-reduz-processamento.md`
- `../../../docs/licoes-aprendidas/LA-010-hooks-terceiros-conflito-operacional.md`
- `../../../docs/licoes-aprendidas/LA-011-endpoint-ajax-mantido-protegido.md`
- `../../../docs/licoes-aprendidas/LA-012-csrf-implicito-endurecimento.md`
- `../../../docs/licoes-aprendidas/LA-013-licoes-aprendidas-no-local-errado.md`

## Arquivos alterados

- `README.md`

## Estrutura afetada

Somente documentacao.

Nao houve alteracao em:

- banco de dados;
- classes PHP;
- hooks;
- telas;
- AJAX;
- permissoes;
- menus;
- assets;
- SQL de instalacao ou uninstall.

## Procedimento de instalacao

Sem mudanca. A documentacao criada nao altera instalacao do plugin.

Procedimento operacional permanece descrito no `README.md` e no
`docs/RELATORIO_TECNICO.md`.

## Procedimento de atualizacao

Sem mudanca funcional.

Para publicar a documentacao no repositorio, basta versionar os arquivos
documentais. Nao e necessario executar install, upgrade, cache clear ou migracao
do GLPI.

## Direitos necessarios

Sem mudanca.

Os direitos do plugin continuam:

- `READ`;
- `CREATE`;
- `UPDATE`;
- `DELETE`;
- `PURGE`;
- fallback operacional para `Config::$rightname` onde previsto no codigo.

## Testes realizados nesta etapa documental

- Consulta da skill principal GLPI Plugins GLPI 10.
- Consulta das referencias `learnings.md` e `anti-patterns.md`.
- Conferencia do estado local do plugin.
- Organizacao das licoes aprendidas no formato recomendado.
- Registro de antipadroes observados, evitados e pendentes.
- Atualizacao dos links de documentacao no `README.md`.

## Limitacoes conhecidas

Esta etapa foi documental. Ela nao corrige os pontos tecnicos identificados na
analise, especialmente:

- endpoint AJAX ainda deve ser endurecido caso continue no projeto;
- CSRF explicito nos POSTs administrativos segue como recomendacao tecnica;
- logs diagnosticos ainda devem ser reduzidos ou condicionados a debug;
- guarda de conflito com hooks de terceiros deve continuar documentada e testada;
- modelo multi-entidade atual usa entidade global `0`, mas nao implementa
  `is_recursive`.

## Procedimento de rollback

Como esta etapa altera apenas documentacao, o rollback consiste em remover ou
reverter:

- `docs/ANTIPADROES.md`;
- `docs/CHECKLIST_DOCUMENTACAO_GLPI10.md`;
- `docs/HISTORICO.md`;
- `docs/RELATORIO_TECNICO.md`;
- alteracao documental do `README.md`.

As licoes aprendidas globais podem ser revertidas removendo os arquivos
`LA-001` a `LA-013` criados em `../../../docs/licoes-aprendidas/` e revertendo o
indice dessa pasta.

Nao ha impacto em dados, tabelas, permissoes ou comportamento do GLPI.

## Proximos passos documentais recomendados

1. A cada correcao futura, adicionar ou atualizar um arquivo proprio em
   `../../../docs/licoes-aprendidas/`.
2. Quando uma licao revelar um padrao recorrente, atualizar
   `docs/ANTIPADROES.md`.
3. Quando houver nova release, atualizar `docs/RELATORIO_TECNICO.md` com versao,
   testes e riscos.
4. Quando os pontos tecnicos pendentes forem corrigidos, registrar a solucao
   aplicada nas licoes correspondentes.

## Checklist da skill

- [x] Historico documentado.
- [x] Relatorio tecnico documentado.
- [x] Testes realizados documentados.
- [x] Erros encontrados documentados.
- [x] Correcoes aplicadas documentadas.
- [x] Limitacoes conhecidas documentadas.
- [x] Rollback documental descrito.
- [x] Licoes aprendidas registradas em arquivos proprios na pasta global do workspace.
- [x] Antipadroes registrados.
- [x] Proximos passos documentados.
- [x] Sem alteracao de codigo nesta etapa.
