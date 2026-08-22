# Checklist de validacao - Relatorio de distribuicoes

## Objetivo

Validar a tela de distribuicoes do plugin Atribuicao Inteligente apos a
simplificacao dos filtros e a revisao das metricas por chamado.

## Ambiente

- GLPI 10.
- Plugin Atribuicao Inteligente instalado e ativo.
- Usuario com direito de leitura do plugin.
- Base com registros em:
  - `glpi_plugin_atribuicaointeligente_distribution_logs`;
  - `glpi_plugin_atribuicaointeligente_decision_logs`.

## Acesso

1. Abrir o GLPI.
2. Acessar a configuracao do plugin Atribuicao Inteligente.
3. Abrir a aba de distribuicoes de chamados.
4. Usar um periodo com chamados distribuidos pelo plugin e manualmente.

## Checklist visual dos filtros

- [ ] A tela exibe somente os filtros principais:
  - Inicio.
  - Fim.
  - Entidade.
  - Categoria.
  - Tipo de distribuicao.
  - Origem.
- [ ] A tela nao exibe mais os filtros:
  - Distribuidor.
  - Tecnico destino.
  - Grupo destino.
  - Entidade do chamado.
  - Entidade origem da transferencia.
- [ ] O filtro Entidade substitui corretamente os filtros antigos de entidade.
- [ ] Ao filtrar, os valores escolhidos permanecem na tela.
- [ ] Ao mudar um filtro e clicar em Filtrar, o resultado reflete a nova escolha.
- [ ] Ao clicar em Limpar, os filtros voltam ao padrao.

## Checklist de entidade

- [ ] Selecionar a entidade onde o plugin atua distribuindo chamados.
- [ ] Confirmar que Distribuicao por categoria nao fica vazia quando existem logs com categoria na entidade.
- [ ] Confirmar que Top 5 tecnicos destino nao fica vazio quando existem tecnicos escolhidos pelo plugin na entidade.
- [ ] Confirmar que Atuacao por chamado exibe Automacao integral quando houver decision log com motivo `Tecnico atribuido automaticamente`.
- [ ] Confirmar que Atuacao por chamado exibe Automacao parcial quando houver decision log com motivo `Tecnico atribuido automaticamente apos atualizacao do chamado`.
- [ ] Confirmar que Atuacao por chamado exibe Atuacao manual quando houver troca manual de tecnico ou transferencia na entidade selecionada.
- [ ] Confirmar que uma acao manual em outra entidade nao transforma em manual um chamado que, na entidade filtrada, teve apenas automacao.
- [ ] Confirmar que tecnicos sem perfil na entidade filtrada nao aparecem em Top 5 tecnicos destino.
- [ ] Confirmar que distribuidores sem perfil na entidade filtrada nao aparecem em Resumo por distribuidor nem em Top 5 distribuidores.

## Checklist de ordenacao e limites

- [ ] Resumo por distribuidor esta limitado a 5 distribuidores.
- [ ] Resumo por distribuidor esta ordenado do maior para o menor por Chamados.
- [ ] A coluna Chamados aparece antes de Transferencias no Resumo por distribuidor.
- [ ] Resumo por distribuidor exibe apenas Chamados e Transferencias.
- [ ] Top 5 distribuidores esta ordenado do maior para o menor por Chamados, nao por eventos.
- [ ] Distribuicao por categoria esta limitada a 10 categorias.
- [ ] Distribuicao por categoria esta ordenada do maior para o menor por Chamados.
- [ ] Top 5 tecnicos destino esta ordenado do maior para o menor por Chamados.
- [ ] Atuacao por chamado esta ordenada do maior para o menor por Chamados.

## Checklist de metricas

- [ ] A tela nao exibe mais cartao ou coluna de Eventos de distribuicao.
- [ ] As tabelas nao exibem coluna Eventos.
- [ ] Chamados distintos considera chamados de logs de distribuicao e de decision logs quando aplicavel.
- [ ] Chamados transferidos mostra a quantidade de chamados distintos transferidos.
- [ ] Transferencias por entidade usa Chamados como metrica principal.
- [ ] Transferencias por entidade nao usa quantidade de eventos como metrica exibida.

## Checklist de graficos e dashboard

- [ ] A tela exibe KPIs visuais no topo para Chamados distintos, Automacao total, Automacao integral, Automacao parcial, Atuacao manual e Transferencias.
- [ ] Os KPIs usam cores semanticas distintas e permanecem legiveis em desktop e notebook.
- [ ] As configuracoes de graficos ficam dentro de uma area expansivel chamada Configuracoes de graficos.
- [ ] Cada bloco configuravel possui seletor de visualizacao.
- [ ] As opcoes disponiveis sao:
  - Pizza.
  - Rosca.
  - Meia torta.
  - Meia rosquinha.
  - Barras.
  - Barras horizontais.
  - Multiplos numeros.
  - Numeros de resumo.
  - Tabela.
- [ ] Evolucao no periodo abre por padrao em Barras verticais.
- [ ] Evolucao no periodo permite agrupar por dia, mes e ano.
- [ ] O numero do volume aparece junto ao grafico em cada modo visual.
- [ ] Alterar o tipo de grafico e filtrar preserva a escolha na tela.
- [ ] Barras horizontais exibem volume proporcional.
- [ ] Barras verticais exibem volume proporcional.
- [ ] Pizza, Rosca, Meia torta e Meia rosquinha exibem um unico grafico consolidado por bloco, com uma fatia para cada item.
- [ ] Graficos circulares exibem legenda com nome, valor e percentual de cada fatia.
- [ ] Graficos circulares nao exibem um grafico separado para cada linha/item.
- [ ] A legenda dos graficos circulares nao vaza para fora do card, inclusive nos blocos de tres colunas.
- [ ] Grafico de pizza exibe o total no centro quando os rotulos estao habilitados.
- [ ] Modo Tabela remove a barra visual e mantem os numeros legiveis.
- [ ] A opcao Usar paleta de gradiente altera a apresentacao sem prejudicar contraste.
- [ ] A opcao Exibir rotulos de valor em pontos / barras habilita e desabilita os numeros no widget sem afetar o modo Tabela.
- [ ] A opcao Limitar numero de dados limita os blocos configuraveis conforme o valor informado.
- [ ] A opcao Cor do plano de fundo altera o fundo dos widgets configuraveis.
- [ ] Cada bloco configuravel permite informar uma cor principal em formato `#RRGGBB`.
- [ ] Cores principais validas permanecem apos clicar em Filtrar.
- [ ] Cores principais validas alteram barras, numeros e a fatia principal dos graficos circulares.
- [ ] Campo de cor principal vazio ou invalido mantem a paleta automatica do relatorio.
- [ ] Atuacao por chamado usa cores semanticas para Automacao integral, Automacao parcial e Atuacao manual.
- [ ] Estados sem dados exibem mensagem limpa e nao deixam area quebrada.

## Cenarios obrigatorios

### Cenario 1 - Automacao integral

1. Criar ou localizar chamado com categoria ja definida.
2. Deixar o plugin atribuir tecnico automaticamente.
3. Abrir o relatorio no periodo do chamado.
4. Filtrar pela entidade do chamado.
5. Validar que o chamado entra em Automacao integral.
6. Validar que a categoria aparece em Distribuicao por categoria.
7. Validar que o tecnico aparece em Top 5 tecnicos destino.

### Cenario 2 - Automacao parcial

1. Criar ou localizar chamado sem tecnico.
2. Atualizar a categoria manualmente.
3. Deixar o plugin atribuir tecnico apos a atualizacao.
4. Abrir o relatorio no periodo do chamado.
5. Filtrar pela entidade do chamado.
6. Validar que o chamado entra em Automacao parcial.
7. Validar que a categoria aparece em Distribuicao por categoria.
8. Validar que o tecnico aparece em Top 5 tecnicos destino.

### Cenario 3 - Atuacao manual

1. Criar ou localizar chamado em que o tecnico foi alterado manualmente.
2. Abrir o relatorio no periodo do chamado.
3. Filtrar pela entidade onde ocorreu a alteracao manual.
4. Validar que o chamado entra em Atuacao manual.

### Cenario 4 - Transferencia

1. Criar ou localizar chamado transferido entre entidades.
2. Abrir o relatorio no periodo da transferencia.
3. Filtrar pela entidade origem.
4. Validar que a transferencia aparece em Transferencias por entidade.
5. Filtrar pela entidade destino.
6. Validar que a transferencia tambem e considerada para a entidade envolvida.
7. Confirmar que a metrica exibida e Chamados, nao eventos.

## Evidencias esperadas

Registrar para cada cenario:

- URL do relatorio com filtros.
- Entidade selecionada.
- Periodo utilizado.
- Print da tela.
- Quantidade em Chamados distintos.
- Quantidade em Chamados transferidos.
- Quantidade e percentual exibidos nos KPIs de automacao.
- Linhas de:
  - Resumo por distribuidor.
  - Distribuicao por categoria.
  - Atuacao por chamado.
  - Top 5 tecnicos destino.
  - Transferencias por entidade.

## Criterios de aceite

- [ ] Nao ha tecnicos de outras entidades em relatorios filtrados por entidade.
- [ ] Nao ha distribuidores de outras entidades em relatorios filtrados por entidade.
- [ ] Automacoes nao somem quando uma entidade e selecionada.
- [ ] Distribuicao por categoria nao depende de limpar o filtro para aparecer.
- [ ] Top 5 tecnicos destino usa Chamados e aparece em ordem decrescente.
- [ ] Top 5 distribuidores usa Chamados e aparece em ordem decrescente.
- [ ] Eventos nao sao exibidos como metrica do relatorio.
- [ ] A tela nao gera erro PHP nem erro JavaScript.

## Resultado

Preencher apos a validacao:

- Status: Pendente / Aprovado / Reprovado.
- Data:
- Validador:
- Observacoes:
- Chamados usados nos testes:
- Evidencias anexadas:
