# Roadmap - Atribuicao Inteligente

Este roadmap registra evolucoes planejadas para o plugin, mantendo como base:

- GLPI 10.0.25 como alvo principal.
- Compatibilidade com a arquitetura de plugins GLPI.
- Nenhuma alteracao no core do GLPI.
- Nenhuma alteracao de schema em tabelas nativas do GLPI.
- Uso de tabelas proprias do plugin para novas regras.
- Permissoes, logs e entidade tratados desde o desenho inicial.

## Fase 1 - Estabilizacao do escopo atual

Prioridade antes de novas funcionalidades.

| Item | Descricao | Complexidade | Impacto |
| --- | --- | --- | --- |
| Testes reais de distribuicao | Validar balanceamento, rodizio, indisponibilidades, logs e retorno apos fim da indisponibilidade. | Media | Alto |
| Testes de permissao | Validar perfis com leitura, criacao, atualizacao, exclusao e acesso direto por URL. | Media | Alto |
| Validacao multi-entidade | Confirmar comportamento de indisponibilidade global e por entidade. | Media | Alto |
| Reduzir logs diagnosticos | Transformar logs verbosos de formulario em logs apenas de erro/acao relevante. | Baixa | Medio |
| Ajustar URLs auxiliares | Garantir que todos os `getFormURL()` e `getSearchURL()` respeitem o parametro `$full`. | Baixa | Baixo |

## Fase 2 - Elegibilidade e gatilhos de distribuicao

Ideias inspiradas no plugin `autoassign`, adaptadas para boas praticas do Atribuicao Inteligente.

### 1. Usar grupo ja atribuido ao chamado

Permitir que a distribuicao use o grupo ja vinculado ao ticket, alem do grupo configurado na categoria ITIL.

Boa pratica proposta:

- Criar opcao de configuracao: `Fonte do grupo`.
- Valores possiveis:
  - grupo da categoria;
  - grupo ja atribuido ao chamado;
  - grupo do chamado com fallback para categoria.
- Nao alterar tabelas nativas para guardar essa preferencia.
- Registrar no log qual fonte de grupo foi usada.

Complexidade: Media
Impacto: Alto

### 2. Distribuir quando um grupo for adicionado ao chamado

Adicionar hook para `Group_Ticket`, permitindo atribuir tecnico quando um grupo encarregado for incluido depois da criacao do chamado.

Boa pratica proposta:

- Executar somente para grupos do tipo encarregado.
- Nao sobrescrever tecnico ja atribuido.
- Reaproveitar o mesmo fluxo de disponibilidade ja existente.
- Registrar log informando que o gatilho foi adicao de grupo.
- Evitar recursao/conflito com outros plugins.

Complexidade: Media
Impacto: Alto

### 3. Lista administrativa de tecnicos elegiveis

Permitir regras permanentes de elegibilidade, separadas de indisponibilidade temporaria.

Modos previstos:

- Exclusao: tecnicos que nunca devem receber distribuicao automatica.
- Inclusao: somente tecnicos selecionados podem receber distribuicao automatica.

Boa pratica proposta:

- Criar tabela relacional propria, evitando listas JSON grandes.
- Respeitar entidade quando aplicavel.
- Registrar motivo no log: tecnico fora da politica de elegibilidade.
- Manter indisponibilidade como camada separada.

Complexidade: Media
Impacto: Alto

### 4. Filtro por perfil permitido

Permitir distribuir apenas para usuarios com determinados perfis GLPI.

Boa pratica proposta:

- Criar configuracao propria por entidade ou global.
- Usar `glpi_profiles_users` apenas para leitura.
- Nao duplicar dados de perfil na tabela do plugin.
- Aplicar o filtro antes da verificacao de carga e disponibilidade.
- Registrar no log quando um tecnico for ignorado por perfil.

Complexidade: Media
Impacto: Medio/Alto

### 5. Criterio de desempate configuravel

Hoje o balanceamento tende a ser deterministico. Uma evolucao util e permitir estrategia de desempate.

Opcoes previstas:

- menor carga + menor ID;
- menor carga + aleatorio;
- menor carga + rodizio entre empatados.

Boa pratica proposta:

- Manter comportamento atual como padrao.
- Registrar criterio usado no log.
- Evitar aleatoriedade como padrao para facilitar auditoria.

Complexidade: Baixa
Impacto: Medio

### 6. Politica de status apos atribuicao

Permitir configurar o que acontece com o status do chamado apos a atribuicao automatica.

Opcoes previstas:

- manter status atual;
- alterar para atribuido;
- alterar para novo.

Boa pratica proposta:

- Manter o comportamento atual como padrao.
- Aplicar somente quando o status atual permitir transicao segura.
- Registrar alteracao no log de decisao.
- Evitar mudar para "Novo" por padrao.

Complexidade: Baixa/Media
Impacto: Medio

### 7. Diagnostico da cadeia de filtros

Exibir, em tela administrativa ou log detalhado, por que cada tecnico foi aceito ou rejeitado.

Ordem sugerida de avaliacao:

1. grupo candidato;
2. perfil permitido;
3. politica de inclusao/exclusao;
4. usuario ativo e nao excluido;
5. indisponibilidade;
6. horario de trabalho, quando existir;
7. carga atual;
8. criterio de desempate.

Boa pratica proposta:

- Nao poluir logs por padrao.
- Ativar detalhamento por configuracao de debug do plugin.
- Usar tabela ou estrutura de log ja existente.

Complexidade: Media
Impacto: Medio

## Fase 3 - Horario de trabalho por tecnico

Evolucao planejada, mas postergada.

Objetivo:

- Permitir que cada tecnico tenha horarios semanais de atendimento.
- Ignorar tecnicos fora do horario de trabalho.
- Combinar horario de trabalho com indisponibilidades ja existentes.

Boa pratica proposta:

- Criar tabela propria para horarios.
- Permitir multiplos intervalos por dia.
- Respeitar entidade.
- Considerar tecnico sem horario cadastrado como disponivel por padrao.
- Adicionar opcao global para exigir horario cadastrado.

Complexidade: Media/Alta
Impacto: Alto

## Ideias que nao devem ser copiadas diretamente

Algumas abordagens vistas em plugins de referencia devem ser evitadas no Atribuicao Inteligente:

- Guardar listas grandes de usuarios/categorias em JSON quando uma tabela relacional for mais adequada.
- Usar `error_log()` como mecanismo principal de auditoria.
- Criar listas enormes de checkboxes sem busca/filtro.
- Alterar status do chamado para "Novo" por padrao.
- Misturar indisponibilidade temporaria com elegibilidade administrativa permanente.

## Ordem recomendada

1. Fechar validacao do escopo atual.
2. Implementar gatilho por grupo atribuido ao chamado.
3. Implementar fonte de grupo configuravel.
4. Implementar elegibilidade de tecnicos por inclusao/exclusao.
5. Implementar filtro por perfil.
6. Implementar desempate configuravel.
7. Implementar politica de status.
8. Implementar horario de trabalho por tecnico.
