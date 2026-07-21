# Documentacao do Sistema de Carregamento

Indice orientado por finalidade. Codigo e testes prevalecem quando um documento historico divergir.

## Comece aqui

| Documento | Finalidade | Fontes no codigo |
|---|---|---|
| [ARQUITETURA.md](ARQUITETURA.md) | Mapa do monorepo, limites, fluxo e padroes | `app/app/Domain/`, `app/routes/`, `mobile/lib/`, `docker-compose*.yml` |
| [MOBILE.md](MOBILE.md) | Setup, navegacao, estado, auth, API e realtime Flutter | `mobile/pubspec.yaml`, `mobile/lib/` |
| [ROTAS.md](ROTAS.md) | Inventario e ownership das rotas API e web | `app/routes/api.php`, `app/routes/web.php`, controllers |
| [regras-negocio.md](regras-negocio.md) | Regras RN, estados, perfis e tolerancias | Enums, Actions e Services de `app/app/Domain/` |
| [api.md](api.md) | Contratos e payloads detalhados da API mobile | `app/routes/api.php`, Form Requests e Resources |
| [DECISOES_TECNICAS.md](DECISOES_TECNICAS.md) | Registro de decisoes arquiteturais | Configuracao e implementacao citadas em cada DT |

## Operacao e integracoes

- [integracao-guardian.md](integracao-guardian.md): protocolo SOAP, formatos e quirks do Guardian.
- [homologacao-ponta-a-ponta.md](homologacao-ponta-a-ponta.md): roteiro de homologacao integrada.
- [CHECKLIST_TESTES_MOBILE.md](CHECKLIST_TESTES_MOBILE.md): verificacao manual do app.
- `carregamento-api.postman_collection.json`: colecao executavel de chamadas HTTP.
- `diagrama-fluxos-regras-negocio.drawio`: diagrama editavel dos fluxos.

## Planejamento e historico

- [STATUS.md](STATUS.md): fotografia operacional mais recente.
- [ROADMAP.md](ROADMAP.md): progresso consolidado.
- [CLAUDE_ROADMAP_CARREGAMENTO.md](CLAUDE_ROADMAP_CARREGAMENTO.md): plano historico das fases 0-10.
- [CLAUDE_ROADMAP_OPERADOR_MOTORISTA.md](CLAUDE_ROADMAP_OPERADOR_MOTORISTA.md): continuacao das fases 11+.
- [PLANO_APP_OPERADOR_MOTORISTA.md](PLANO_APP_OPERADOR_MOTORISTA.md): plano de evolucao Flutter.
- [CHANGELOG.md](CHANGELOG.md): mudancas entregues.

Roadmaps e planos registram intencao e podem conter exemplos antigos. Para comportamento vigente,
confirme rotas, enums, Actions, migrations e testes.

