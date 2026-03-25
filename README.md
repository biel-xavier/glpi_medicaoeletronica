# Medicao Eletronica

Plugin de integracao de fechamento de tickets com servico externo de medicao via SOAP.

## Documentos

- `USER_GUIDE.md`: guia operacional para configuracao e uso
- `TECHNICAL_GUIDE.md`: referencia tecnica de hooks, payload, banco e integracao

## Resumo funcional

Quando um ticket de categoria configurada e fechado, o plugin valida os dados obrigatorios, bloqueia o fechamento se houver inconsistencias e, se tudo estiver correto, envia a medicao para um endpoint SOAP.

## Dependencias principais

- categorias configuradas no plugin
- contrato vinculado ao ticket
- tecnico responsavel e grupo
- dados auxiliares do plugin Fields
- localidade e custos preenchidos

