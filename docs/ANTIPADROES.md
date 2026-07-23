# Antipadroes observados e evitados - Atribuicao Inteligente

Este documento registra antipadroes relevantes identificados durante o projeto
**Atribuicao Inteligente**, conectando-os as licoes aprendidas e ao estado atual
do plugin.

> Este e um relatorio historico especifico do plugin. A base normativa e
> reutilizavel de antipadroes do workspace fica em
> `docs/padroes/antipadroes-glpi-plugins.md`.

## AP-001 - Interceptar dropdown nativo do GLPI globalmente

### Categoria

Dropdown, AJAX, Compatibilidade.

### Descricao

Alterar o comportamento global de dropdowns nativos do GLPI para aplicar regra
visual especifica do plugin.

### Por que evitar

Dropdowns do GLPI sao reutilizados por filtros, formularios, modais e acoes em
massa. Uma interceptacao global pode quebrar contextos que nao possuem os mesmos
parametros do formulario alvo.

### Estado no plugin

Evitado. A interceptacao global foi removida.

### Pratica recomendada

Manter dropdown nativo e validar regra critica no backend. Qualquer filtro visual
futuro deve ser local, opt-in e compatvel com o contrato nativo.

### Licoes relacionadas

- LA-001
- LA-011

---

## AP-002 - Confiar apenas no frontend para regra critica

### Categoria

Seguranca, Backend, Regras de negocio.

### Descricao

Bloquear tecnico indisponivel apenas removendo-o ou escondendo-o no campo visual.

### Por que evitar

Requisicoes podem vir de API, acao em massa, outro plugin, formulario customizado
ou chamada direta. O frontend nao e fronteira de seguranca.

### Estado no plugin

Evitado. O bloqueio de tecnico indisponivel ocorre no hook `pre_item_add` de
`Ticket_User`.

### Pratica recomendada

Toda regra critica deve ser validada no servidor.

### Licoes relacionadas

- LA-001
- LA-002

---

## AP-003 - Validar permissao apenas na tela

### Categoria

Permissoes, Seguranca.

### Descricao

Permitir que a tela esconda botoes, mas o POST execute sem validar novamente o
direito da acao.

### Por que evitar

Usuario pode chamar URL diretamente ou reenviar POST manualmente.

### Estado no plugin

Evitado na maior parte das acoes. O plugin usa asserts especificos para criar,
atualizar e excluir indisponibilidades e escalas.

### Pratica recomendada

Toda acao deve validar direito novamente no POST.

### Licoes relacionadas

- LA-002

---

## AP-004 - Endpoint AJAX sem direito e entidade explicitos

### Categoria

AJAX, Seguranca, Multi-Entidade.

### Descricao

Permitir que um endpoint AJAX responda apenas com validacao generica de acesso
central.

### Por que evitar

Mesmo endpoints auxiliares podem vazar informacoes ou operar fora da entidade
autorizada.

### Estado no plugin

Parcialmente pendente. O endpoint de tecnicos disponiveis nao e mais carregado
globalmente, mas permanece no projeto e deve ser endurecido caso continue
existindo.

### Pratica recomendada

Validar direito do plugin, entidade e parametros antes de retornar dados.

### Licoes relacionadas

- LA-011

---

## AP-005 - Ignorar CSRF em POST administrativo

### Categoria

Seguranca.

### Descricao

Confiar apenas em protecao implicita sem deixar claro no ponto da acao que o
token foi validado.

### Por que evitar

POSTs administrativos criam, alteram e excluem dados de operacao. A validacao
explicita facilita auditoria e reduz risco em mudancas futuras do bootstrap.

### Estado no plugin

Pendente como endurecimento. Formularios geram token e o projeto assume validacao
do GLPI, mas a documentacao recomenda validar explicitamente.

### Pratica recomendada

Validar token CSRF explicitamente antes de salvar, excluir, ativar ou desativar.

### Licoes relacionadas

- LA-012

---

## AP-006 - Criar registros operacionais ativos por padrao

### Categoria

Seguranca operacional, Banco de Dados.

### Descricao

Ativar automaticamente categorias ou regras que alteram o comportamento de
chamados sem decisao administrativa explicita.

### Por que evitar

Pode alterar distribuicao de chamados em areas ainda nao homologadas.

### Estado no plugin

Evitado. Categorias novas entram inativas por padrao.

### Pratica recomendada

Funcionalidades que alteram fluxo operacional devem nascer em modo opt-in.

### Licoes relacionadas

- LA-006

---

## AP-007 - Gerar logs diagnosticos excessivos em producao

### Categoria

Performance, Operacao, Auditoria.

### Descricao

Registrar acessos e POSTs administrativos com detalhes de contexto em volume
normal de producao.

### Por que evitar

Pode aumentar volume de log, dificultar auditoria e misturar diagnostico
temporario com eventos relevantes.

### Estado no plugin

Parcialmente pendente. A documentacao registra que logs diagnosticos devem ser
reduzidos ou condicionados a debug.

### Pratica recomendada

Registrar erros e acoes relevantes por padrao; logs detalhados somente com modo
debug.

### Licoes relacionadas

- LA-002

---

## AP-008 - Assumir que o plugin esta sozinho

### Categoria

Hooks, Compatibilidade.

### Descricao

Ignorar que plugins como Escalade e Behaviors podem atuar nos mesmos objetos e
hooks.

### Por que evitar

Pode gerar recursao, duplicidade de acoes, conflito silencioso e comportamento
dificil de reproduzir.

### Estado no plugin

Mitigado, mas com solucao sensivel. Existe guarda que desativa temporariamente
hooks especificos de outros plugins e restaura depois.

### Pratica recomendada

Preferir idempotencia e travas internas. Quando houver excecao, documentar e
testar coexistencia com plugins conhecidos.

### Licoes relacionadas

- LA-010

---

## AP-009 - Nao documentar rollback e upgrade

### Categoria

Documentacao, Operacao.

### Descricao

Entregar alteracao sem orientar como atualizar ou voltar atras.

### Por que evitar

Ambientes GLPI corporativos precisam de manutencao previsivel e reversivel.

### Estado no plugin

Evitado. O relatorio tecnico registra comando de upgrade recomendado e alerta
para nao usar uninstall em upgrade.

### Pratica recomendada

Documentar instalacao, upgrade, limitacoes e rollback operacional.

### Licoes relacionadas

- LA-008

---

## AP-010 - Usar SQL manual onde API estruturada bastaria

### Categoria

Banco de Dados, Manutenibilidade.

### Descricao

Montar consultas SQL manualmente quando o DB API do GLPI poderia expressar a
consulta com criterios estruturados.

### Por que evitar

Mesmo com casts, SQL manual aumenta custo de revisao e risco em futuras
alteracoes.

### Estado no plugin

Aceito como divida tecnica pontual. As consultas revisadas usam casts inteiros e
tabelas internas conhecidas, mas futuras alteracoes devem preferir `$DB->request`
quando viavel.

### Pratica recomendada

Usar APIs estruturadas do GLPI para consultas e manter SQL manual apenas quando
necessario por desempenho ou expressividade.

### Licoes relacionadas

- LA-008
- LA-009
