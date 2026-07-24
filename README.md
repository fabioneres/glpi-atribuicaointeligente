# Atribuição Inteligente

<p align="center">
  <img src="atribuicaointeligente.png" alt="Atribuicao Inteligente" width="180">
</p>

Plugin GLPI para atribuição automática de chamados a técnicos por categoria/grupo, com suporte a indisponibilidade e escala de atendimento de técnicos.

## Base

- GLPI 10.0.x, com foco em GLPI 10.0.25.
- Fork standalone baseado no módulo **SmartAssign** do plugin **NexTool Solutions**.
- Autor deste fork: **Fabio Neres**.
- Licença: GPLv3+.
- Versão atual: **1.1.8**.

## Referências

Este fork preserva a lógica principal do SmartAssign/NexTool para distribuição por balanceamento ou rodízio, adaptando-a para um plugin GLPI independente chamado `atribuicaointeligente`.

Referência original:

- NexTool Solutions / SmartAssign, por Richard Loureiro / RPGMais.
- Repositório público de referência: https://github.com/RPGMais/nextool

## Instalação

Copie a pasta `atribuicaointeligente` para o diretório `plugins` do GLPI:

```text
GLPI_ROOT/plugins/atribuicaointeligente
```

Depois acesse **Configurar > Plugins**, instale e ative o plugin **Atribuição Inteligente**.

## Permissões

O plugin cria o direito `plugin_atribuicaointeligente` em **Administração > Perfis > Atribuição Inteligente**.

- `Ler`: acessa as telas e logs do plugin.
- `Criar`: adiciona indisponibilidades e escalas de atendimento.
- `Atualizar`: altera configurações, categorias, indisponibilidades e escalas de atendimento.
- `Excluir/Purgar`: remove indisponibilidades e escalas de atendimento.

## Recursos

Consulte tambem:

- [ROADMAP.md](ROADMAP.md) para evolucoes planejadas;
- [docs/README.md](docs/README.md) para o indice da documentacao;
- [docs/HISTORICO.md](docs/HISTORICO.md) para o historico resumido;
- [docs/RELATORIO_TECNICO.md](docs/RELATORIO_TECNICO.md) para o relatorio tecnico consolidado;
- [../../../docs/licoes-aprendidas/README.md](../../../docs/licoes-aprendidas/README.md) para licoes aprendidas globais do workspace;
- [docs/ANTIPADROES.md](docs/ANTIPADROES.md) para antipadroes observados e evitados;
- [docs/CHECKLIST_DOCUMENTACAO_GLPI10.md](docs/CHECKLIST_DOCUMENTACAO_GLPI10.md) para a conformidade documental com a skill GLPI 10.

- Atribuição automática por balanceamento.
- Atribuição automática por rodízio.
- Opção para atribuir também o grupo encarregado da categoria.
- Opção para ignorar gerentes do grupo.
- Cadastro de indisponibilidade de técnicos:
  - férias por período;
  - ausência em data específica;
  - indisponibilidade recorrente por dia da semana;
  - ausência temporária com data inicial e final.
- Cadastro de escala de atendimento por técnico:
  - múltiplos dias da semana no mesmo cadastro;
  - horário inicial e final;
  - validade opcional por período;
  - escopo global ou por entidade.
- Log de decisões de atribuição com técnico escolhido e técnicos ignorados.
- Habilitacao do plugin por entidade, com reducao de logs e processamento fora do escopo desejado.
- Entidades entram inativas por padrao em instalacoes novas; habilite manualmente apenas as entidades desejadas.
- Acoes rapidas para habilitar ou desabilitar todas as entidades visiveis de uma vez.
- Paginacao na tela de logs para evitar carregamento excessivo.
- Opção para respeitar o calendário de atendimento da entidade; entidades sem calendário continuam em modo 24/7.
- Opcao para atribuir chamados tambem ao atualizar, usando somente categorias ativas e apenas quando o chamado ainda nao possui tecnico.
- Bloqueio server-side para impedir gravações diretas de técnicos indisponíveis em atribuições manuais.
- Categorias novas entram inativas por padrão, para que a atribuição automática seja habilitada somente onde desejado.

## Observação sobre migração

Ao instalar, o plugin tenta copiar configurações e categorias do SmartAssign/NexTool se as tabelas originais existirem. Nenhuma tabela nativa do GLPI é alterada.
