## Solução Implementada

### Tecnologias Utilizadas

- **Laravel Octane:** Para gerenciar múltiplas requisições simultâneas, garantindo alta performance e eficiência no atendimento das requisições.
- **Cache do Laravel:** Utilizado com o driver `file` para armazenar transações rapidamente no sistema de arquivos.
- **Jobs:** Implementado um `Job` assíncrono para remover todas as transações em segundo plano, evitando impacto na performance do sistema.
- **Comando em Background:** Um `Command` é executado a cada segundo para atualizar as estatísticas das transações, incluindo soma, média, máximo, mínimo e contagem.

### Funcionalidade

1. **Armazenamento de Transações:**
   - As transações são armazenadas no cache do Laravel usando o driver `file`. Cada transação tem um tempo de expiração configurado para garantir que dados antigos sejam removidos.

2. **Exclusão de Transações:**
   - Um `Job` assíncrono é utilizado para remover todas as transações do cache, o que é feito de forma eficiente sem impactar a performance do sistema. (php artisan queue:work)

3. **Atualização de Estatísticas:**
   - Um comando é configurado para rodar em background a cada segundo, atualizando estatísticas como soma, média, máximo, mínimo e contagem das transações. Essas estatísticas são armazenadas no cache e estão disponíveis para consulta. ( php artisan schedule:run )

### Resumo

A solução implementada aproveita o Laravel Octane para alta performance e o cache para armazenamento eficiente de dados. A exclusão das transações é realizada via `Job` assíncrono, e a atualização das estatísticas é gerenciada por um comando que roda em segundo plano a cada segundo, garantindo que as informações estejam sempre atualizadas e disponíveis.
