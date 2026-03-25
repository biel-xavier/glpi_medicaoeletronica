# Medicao Eletronica Technical Guide

## Arquitetura

- `setup.php`: registro do plugin e controllers de API
- `hook.php`: instalacao, tabelas e hooks
- `inc/TicketHook.php`: eventos de fechamento
- `inc/Service/MedicaoService.php`: servicos principais
- `inc/Service/MedicaoPayloadBuilder.php`: montagem do payload
- `inc/Client/MedicaoSoapClient.php`: cliente SOAP
- `inc/Repository/TicketRepository.php`: consulta dos dados do ticket
- `inc/Repository/ConfigRepository.php`: persistencia de configuracao
- `inc/ApiController.php`: endpoints auxiliares

## Hooks

- `pre_item_update` em `Ticket`
- `item_update` em `Ticket`

## Regra de disparo

- apenas status `6`
- apenas categorias configuradas

## Tabelas

- `glpi_plugin_medicaoeletronica_configs`
- `glpi_plugin_medicaoeletronica_histories`
- `glpi_plugin_medicaoeletronica_locations`

## Fluxo tecnico

1. `handlePreItemUpdate` detecta tentativa de fechamento
2. `getDataTicket` monta payload
3. `validateBeforeSendMedicao` verifica obrigatorios
4. se falhar, bloqueia o fechamento
5. `handleItemUpdate` envia via `forceSendMedicao`
6. `MedicaoSoapClient` executa retry exponencial e registra historico

## Campos obrigatorios

- `cidade_destino`
- `cidade_origem`
- `cod_adm_usuario_parceiro`
- `cod_ata_chamado`
- `cod_fin_ccusto`
- `cod_int_fin_ccusto`
- `data_abertura`
- `data_chegada`
- `data_termino`
- `data_fechamento`
- `historico_fechamento`
- `nome_localidade`
- `nro_adv`
- `nro_oc`
- `razao_social_parceiro`

## Dependencias do banco

- tecnico em `glpi_tickets_users` com `type = 2`
- parceiro em `glpi_plugin_fields_groupdadosparceiros`
- centro de custo em `glpi_plugin_fields_contractdadoscentrodecustos`
- dados de custos, followups, entidade e localidade

## API

- `GET /medicaoeletronica/getPartners`
- `GET /medicaoeletronica/getContactsPartner?id=...`
- `GET /medicaoeletronica/getContactsExecutivePartner?id=...`
- `GET /medicaoeletronica/getPartnersCapillarity?id=...`
- `GET /medicaoeletronica/getCostCenter`
- `GET /medicaoeletronica/getDataTicket?id=...`
- `POST /medicaoeletronica/forceSendMedicao?id=...`
- `GET /medicaoeletronica/version`

## Contrato SOAP

- action `GravarChamado`
- `Content-Type: text/xml; charset=utf-8`
- `SOAPAction: http://oakmontgroup.com.br/GravarChamado`
- sucesso reconhecido pela mensagem `Chamado integrado com sucesso`

## Observacoes

- existe `CHAVE_ACESSO` fixa no builder atual
- retry exponencial simples conforme o numero de tentativas configurado
- falhas sao registradas no log `medicao_eletronica`

