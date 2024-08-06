# Serviço de Transações com Swoole e Laravel Octane

Este projeto implementa um serviço de transações utilizando Swoole e Laravel Octane para melhorar o desempenho e gerenciar múltiplas requisições de forma eficiente. A seguir está uma descrição detalhada das funcionalidades implementadas e os desafios encontrados durante o desenvolvimento.

## Funcionalidades

### 1. Armazenamento de Transações
- O método `store` recebe uma requisição de transação, valida o timestamp e armazena a transação na tabela Swoole (`swoole.transactions`).
- As transações são armazenadas utilizando uma chave única composta pelo timestamp e o valor da transação.
- O método também atualiza uma tabela de estatísticas Swoole (`swoole.stats`) com os novos valores.
- Utiliza o cache do Octane para armazenamento temporário.

### 2. Recuperação de Todas as Transações
- O método `getAllTransactions` recupera todas as transações da tabela Swoole e retorna uma resposta JSON com esses dados.

### 3. Estatísticas das Transações
- O método `statistics` retorna as estatísticas das transações armazenadas, como soma, média, máximo, mínimo e contagem.

### 4. Deletar Todas as Transações
- O método `deleteAll` remove todas as transações da tabela Swoole e reseta as estatísticas.

### 5. Salvar Transações em um Arquivo JSON
- O método `saveTableToJson` salva o estado atual da tabela Swoole de transações em um arquivo JSON para persistência dos dados.

## Desafios

### 1. Integração com Kafka
- Foi tentado utilizar Kafka para a integração, mas houve problemas na integração e falta de tempo para finalizar a implementação do desafio.

### 2. Uso de Octane com Swoole
- Utilizou-se Octane para obter mais desempenho, aproveitando a capacidade do Swoole de trabalhar com múltiplos workers, gerenciando múltiplas requisições de forma eficiente.

### 3. Persistência de Dados
- Um dos maiores desafios foi a persistência dos dados. A necessidade de ter esses dados persistidos em algum lugar foi crucial para a implementação de um serviço em background (job ou command) que atualizasse um JSON a cada segundo, calculando as estatísticas em tempo real.
- A dificuldade estava em garantir que os dados na tabela Swoole fossem persistidos corretamente para que pudessem ser usados por serviços de background para cálculos de estatísticas.

### 4. Gestão de Memória e Garbage Collection
- A gestão da memória foi um dos problemas críticos. O threshold de Garbage Collection do Swoole precisava ser configurado corretamente para evitar o acúmulo de dados na memória.
- A falta de um gerenciamento adequado de memória poderia levar a problemas de desempenho e ao esgotamento dos recursos do sistema.

### 5. Gestor de Transações em Background
- Planejou-se implementar um gestor de transações no serviço em background para monitorar quais transações estavam sendo salvas e remover as mais antigas.
- Esse gestor ajudaria a manter a tabela de transações limpa e garantiria que apenas as transações relevantes permanecessem na memória, otimizando o uso dos recursos.

## Instalação

1. Clone o repositório
    ```sh
    git clone https://github.com/seunomeusuario/servico-transacoes.git
    cd servico-transacoes
    ```

2. Instale as dependências
    ```sh
    composer install
    npm install
    ```

3. Configure seu arquivo `.env`
    ```sh
    cp .env.example .env
    ```

4. Execute as migrações
    ```sh
    php artisan migrate
    ```

5. Inicie o Octane
    ```sh
    php artisan octane:start
    ```

## Uso

- **Armazenar uma transação:** POST para `/transactions` com `amount` e `timestamp`.
- **Recuperar todas as transações:** GET para `/transactions`.
- **Obter estatísticas:** GET para `/transactions/statistics`.
- **Deletar todas as transações:** DELETE para `/transactions`.

## Melhorias Futuras

- Resolver a integração com Kafka para melhor gerenciamento de mensagens e eventos.
- Implementar uma solução robusta para persistência de dados, garantindo atualizações de estatísticas em tempo real via um serviço de background.
- Melhorar os mecanismos de tratamento de erros e validação dos dados de transação.
- Configurar corretamente o threshold de Garbage Collection do Swoole para otimizar a gestão de memória.
- Implementar um gestor de transações em background para monitorar e remover transações antigas, mantendo a tabela de transações otimizada.

## Licença

Este projeto está licenciado sob a Licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.
