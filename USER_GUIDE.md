# Medicao Eletronica User Guide

## Objetivo

O `medicaoeletronica` valida tickets no fechamento e envia dados para um sistema externo de medicao.

## Configuracao

Campos da tela de configuracao:

- `url`: endpoint SOAP
- `retries`: numero de tentativas de envio
- `itilcategories[]`: categorias que participam da integracao

## Como usar

1. abrir a configuracao do plugin
2. preencher a URL do servico
3. ajustar o numero de tentativas
4. selecionar as categorias elegiveis
5. salvar
6. garantir que os tickets dessas categorias tenham os dados obrigatorios completos

## O que acontece no fechamento

- o plugin detecta que o ticket esta indo para status `6`
- valida os dados obrigatorios do payload
- se houver erro, impede o fechamento
- se nao houver erro, apos salvar envia a integracao SOAP

## Dados obrigatorios mais sensiveis

- cidades de origem e destino
- codigos administrativos e financeiros
- datas do ticket
- historico de fechamento
- numero ADV
- numero OC
- nome da localidade
- razao social do parceiro

## Dependencias de cadastro

- tecnico vinculado ao ticket
- grupo do tecnico
- contrato no ticket
- localidade preenchida
- dados do parceiro e centro de custo nas tabelas do plugin Fields

## Problemas comuns

- ticket nao fecha: algum campo obrigatorio nao foi encontrado
- envio nao acontece: categoria nao configurada ou endpoint indisponivel
- retorno nao reconhecido: servico externo nao devolveu a mensagem de sucesso esperada

