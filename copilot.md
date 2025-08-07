@workspace 
atualmente quero usar para mexer com usuário:
#file:User.php 
#file:UserDTO.php 
#file:UserRepositoryInterface.php 
#file:EloquentUserRepository.php 
#file:UserPolicy.php 
#file:UserResource.php 
#file:StoreUserRequest.php 
#file:UpdateUserRequest.php 
#file:UserController.php 
#file:UserFactory.php 
#file:UserSeeder.php 
#file:UserService.php 
#file:CreateUserCommand.php 
#file:GetUserByIdQuery.php 

não sei se estou usando tudo ou se tem mais coisa que eu poderia usar e que tenha algo relacionado ao user, verifique se todos esses arquivos estão sendo usados e se não tem mais algum que devia ser usado mas não foi


===
resposta que me deu:

## 📋 Arquitetura CQRS Correta: Controller → Service → Commands/Queries → Repository
### Fluxo correto:
- **Controller**: Apenas coordena (request/response) e chama **Service**
- **Service**: Contém lógica de negócio e orquestra **Commands/Queries**
- **Commands**: Operações de escrita (Create, Update, Delete)
- **Queries**: Operações de leitura (Get, Find, List)
- **Commands/Queries**: Operações específicas que usam **Repository**
- **Repository**: Acesso direto aos dados
- **Policy** → autoriza ações
- **Requests** → validam dados
- **Resources** → transformam responses
- **DTOs** → transferem dados entre camadas


## Arquivos que **ESTÃO sendo usados** corretamente:
✅ `User.php` - Model principal usado em todo o sistema
✅ `UserDTO.php` - Usado no UserController e nos Commands
✅ `UserRepositoryInterface.php` - Interface implementada pelo EloquentUserRepository
✅ `EloquentUserRepository.php` - Implementação usada pelos Commands/Queries
✅ `UserPolicy.php` - Usado no UserController via `authorizeResource()`
✅ `UserResource.php` - Usado no UserController para transformar responses
✅ `StoreUserRequest.php` - Usado no método `store()` do UserController
✅ `UpdateUserRequest.php` - Usado no método `update()` do UserController
✅ `UserController.php` - Controller principal da API que usa apenas o UserService
✅ `UserFactory.php` - Usado no UserSeeder
✅ `UserSeeder.php` - Registrado no DatabaseSeeder
✅ `UserService.php` - Usado pelo UserController e orquestra Commands/Queries
✅ `CreateUserCommand.php` - Usado pelo UserService
✅ `GetUserByIdQuery.php` - Usado pelo UserService
✅ `UpdateUserCommand.php` - Usado pelo UserService para updates
✅ `DeleteUserCommand.php` - Usado pelo UserService para deletes  
✅ `GetAllUsersQuery.php` - Usado pelo UserService para paginação
✅ `GetUserByEmailQuery.php` - Usado pelo UserService para busca por email
✅ `Profile.php` - Model relacionado ao User
✅ `ProfileResource.php` - Usado no UserResource para incluir dados do perfil
✅ `ProfileFactory.php` - Usado para criar perfis nos testes/seeders
✅ `SendWelcomeEmailJob.php` - Dispatched no UserController após criar usuário
✅ `RepositoryServiceProvider.php` - Registra as interfaces e implementações

=== novo prompt

compare com o que eu tenho, veja se todos os testes de User estão feitos e localizados no lugares corretos:
//diretório app:
C:\USERS\MURILO CARAZATO\DOCUMENTS\LARAVEL PROJECTS\HUB\TESTE-TEMPLATE\TESTS
│   TestCase.php
│   
├───Feature
│   │   ExampleTest.php
│   │   UserWorkflowTest.php
│   │   
│   └───User
│           CreateUserTest.php
│           DeleteUserTest.php
│           GetUsersTest.php
│           UpdateUserTest.php
│
└───Unit
    │   ExampleTest.php
    │   
    ├───Commands
    │   └───User
    │           CreateUserCommandTest.php
    │           DeleteUserCommandTest.php
    │           UpdateUserCommandTest.php
    │
    ├───DTO
    │       UserDTOTest.php
    │       
    ├───Http
    │   ├───Controllers
    │   │   │   ApiControllerTest.php
    │   │   │   
    │   │   └───Api
    │   │       └───V1
    │   │               UserControllerTest.php
    │   │
    │   └───Middleware
    │           ApiRateLimitMiddlewareTest.php
    │           CacheResponseMiddlewareTest.php
    │           RefreshTokenMiddlewareTest.php
    │
    ├───Jobs
    │       ProcessUserRegistrationTest.php
    │       SendWelcomeEmailJobTest.php
    │
    ├───Models
    │       UserTest.php
    │
    ├───Policies
    │       UserPolicyTest.php
    │
    ├───Queries
    │   └───User
    │           GetAllUsersQueryTest.php
    │           GetUserByEmailQueryTest.php
    │           GetUserByIdQueryTest.php
    │
    ├───Repositories
    │       EloquentUserRepositoryTest.php
    │
    ├───Requests
    │   └───User
    │           StoreUserRequestTest.php
    │           UpdateUserRequestTest.php
    │
    ├───Resources
    │       UserResourceTest.php
    │
    └───Services
            BatchProcessorServiceTest.php
            CacheServiceTest.php
            ExportServiceTest.php
            FileStorageServiceTest.php
            UserServiceTest.php

PS C:\Users\Murilo Carazato\Documents\Laravel Projects\HUB\teste-template> tree app /F  
Listagem de caminhos de pasta
O número de série do volume é 2008-4D1F
C:\USERS\MURILO CARAZATO\DOCUMENTS\LARAVEL PROJECTS\HUB\TESTE-TEMPLATE\APP
├───Console
│   │   Kernel.php
│   │
│   └───Commands
│           SetupHorizonWorkers.php
│           WorkerHealthCheck.php
│
├───Domains
│   ├───Auth
│   │   ├───Commands
│   │   │       LoginCommand.php
│   │   │
│   │   ├───Contracts
│   │   │       AuthServiceInterface.php
│   │   │
│   │   ├───Queries
│   │   │       GetCurrentUserQuery.php
│   │   │
│   │   └───Services
│   │           AuthService.php
│   │
│   ├───Core
│   │   └───Services
│   │           AuditService.php
│   │           BatchProcessorService.php
│   │           CacheService.php
│   │           ExportService.php
│   │           FeatureFlagService.php
│   │           FileStorageService.php
│   │           MonitoringService.php
│   │           NotificationService.php
│   │           QueueManagerService.php
│   │           RateLimitService.php
│   │           WebhookService.php
│   │
│   └───User
│       ├───Commands
│       │       CreateUserCommand.php
│       │       DeleteUserCommand.php
│       │       UpdateUserCommand.php
│       │
│       ├───Queries
│       │       GetAllUsersQuery.php
│       │       GetUserByEmailQuery.php
│       │       GetUserByIdQuery.php
│       │
│       └───Services
│               UserService.php
│
├───DTO
│       AuditDTO.php
│       ProfileDTO.php
│       UserDTO.php
│
├───Exceptions
│       Handler.php
│
├───Http
│   │   Kernel.php
│   │
│   ├───Controllers
│   │   │   ApiController.php
│   │   │   ApiDocumentationController.php
│   │   │   Controller.php
│   │   │
│   │   └───Api
│   │       └───V1
│   │               AuthController.php
│   │               FeatureFlagController.php
│   │               HealthCheckController.php
│   │               NotificationController.php
│   │               ProfileController.php
│   │               UserController.php
│   │               WebhookController.php
│   │
│   ├───Middleware
│   │       ApiMetricsMiddleware.php
│   │       ApiRateLimitMiddleware.php
│   │       CacheResponseMiddleware.php
│   │       RefreshTokenMiddleware.php
│   │
│   ├───Requests
│   │   ├───Auth
│   │   │       LoginRequest.php
│   │   │
│   │   └───User
│   │           StoreUserRequest.php
│   │           UpdateUserRequest.php
│   │
│   └───Resources
│           ProfileResource.php
│           UserResource.php
│
├───Jobs
│       BatchProcessor.php
│       ProcessUserRegistration.php
│       QueueMonitoringJob.php
│       SendWelcomeEmailJob.php
│
├───Mail
│       WelcomeEmail.php
│
├───Models
│       Audit.php
│       Feature.php
│       FeatureUser.php
│       Profile.php
│       User.php
│
├───Policies
│       UserPolicy.php
│
├───Providers
│       AppServiceProvider.php
│       AuthServiceProvider.php
│       DomainServiceProvider.php
│       ExceptionServiceProvider.php
│       RepositoryServiceProvider.php
│       RouteServiceProvider.php
│
└───Repositories
    ├───Eloquent
    │       EloquentAuditRepository.php
    │       EloquentProfileRepository.php
    │       EloquentUserRepository.php
    │
    └───Interfaces
            AuditRepositoryInterface.php
            ProfileRepositoryInterface.php
            UserRepositoryInterface.php

//diretório tests
C:\USERS\MURILO CARAZATO\DOCUMENTS\LARAVEL PROJECTS\HUB\TESTE-TEMPLATE\TESTS
│   TestCase.php
│   
├───Feature
│   │   ExampleTest.php
│   │   UserWorkflowTest.php
│   │   
│   └───User
│           CreateUserTest.php
│           DeleteUserTest.php
│           GetUsersTest.php
│           UpdateUserTest.php
│
└───Unit
    │   ExampleTest.php
    │   
    ├───Commands
    │   └───User
    │           CreateUserCommandTest.php
    │           DeleteUserCommandTest.php
    │           UpdateUserCommandTest.php
    │
    ├───DTO
    │       UserDTOTest.php
    │       
    ├───Http
    │   ├───Controllers
    │   │   │   ApiControllerTest.php
    │   │   │   
    │   │   └───Api
    │   │       └───V1
    │   │               UserControllerTest.php
    │   │
    │   └───Middleware
    │           ApiRateLimitMiddlewareTest.php
    │           CacheResponseMiddlewareTest.php
    │           RefreshTokenMiddlewareTest.php
    │
    ├───Jobs
    │       ProcessUserRegistrationTest.php
    │       SendWelcomeEmailJobTest.php
    │
    ├───Models
    │       UserTest.php
    │
    ├───Policies
    │       UserPolicyTest.php
    │
    ├───Queries
    │   └───User
    │           GetAllUsersQueryTest.php
    │           GetUserByEmailQueryTest.php
    │           GetUserByIdQueryTest.php
    │
    ├───Repositories
    │       EloquentUserRepositoryTest.php
    │
    ├───Requests
    │   └───User
    │           StoreUserRequestTest.php
    │           UpdateUserRequestTest.php
    │
    ├───Resources
    │       UserResourceTest.php
    │
    └───Services
            BatchProcessorServiceTest.php
            CacheServiceTest.php
            ExportServiceTest.php
            FileStorageServiceTest.php
            UserServiceTest.php

===

criei um projeto padrão com ia, esses foram os arquivos que foram criados:
├───Console
│   │   Kernel.php
│   │   
│   └───Commands
│           SetupHorizonWorkers.php
│           WorkerHealthCheck.php
│
├───Domains
│   ├───Auth
│   │   ├───Commands
│   │   │       LoginCommand.php
│   │   │       
│   │   ├───Contracts
│   │   │       AuthServiceInterface.php
│   │   │       
│   │   ├───Queries
│   │   │       GetCurrentUserQuery.php
│   │   │       
│   │   └───Services
│   │           AuthService.php
│   │
│   ├───Core
│   │   └───Services
│   │           AuditService.php
│   │           BatchProcessorService.php
│   │           CacheService.php
│   │           ExportService.php
│   │           FeatureFlagService.php
│   │           FileStorageService.php
│   │           MonitoringService.php
│   │           NotificationService.php
│   │           QueueManagerService.php
│   │           RateLimitService.php
│   │           WebhookService.php
│   │
│   └───User
│       ├───Commands
│       │       CreateUserCommand.php
│       │       DeleteUserCommand.php
│       │       UpdateUserCommand.php
│       │
│       ├───Queries
│       │       GetAllUsersQuery.php
│       │       GetUserByEmailQuery.php
│       │       GetUserByIdQuery.php
│       │
│       └───Services
│               UserService.php
│
├───DTO
│       AuditDTO.php
│       ProfileDTO.php
│       UserDTO.php
│
├───Exceptions
│       Handler.php
│
├───Http
│   │   Kernel.php
│   │
│   ├───Controllers
│   │   │   ApiController.php
│   │   │   ApiDocumentationController.php
│   │   │   Controller.php
│   │   │
│   │   └───Api
│   │       └───V1
│   │               AuthController.php
│   │               FeatureFlagController.php
│   │               HealthCheckController.php
│   │               NotificationController.php
│   │               ProfileController.php
│   │               UserController.php
│   │               WebhookController.php
│   │
│   ├───Middleware
│   │       ApiMetricsMiddleware.php
│   │       ApiRateLimitMiddleware.php
│   │       CacheResponseMiddleware.php
│   │       RefreshTokenMiddleware.php
│   │
│   ├───Requests
│   │   ├───Auth
│   │   │       LoginRequest.php
│   │   │
│   │   └───User
│   │           StoreUserRequest.php
│   │           UpdateUserRequest.php
│   │
│   └───Resources
│           ProfileResource.php
│           UserResource.php
│
├───Jobs
│       BatchProcessor.php
│       ProcessUserRegistration.php
│       QueueMonitoringJob.php
│       SendWelcomeEmailJob.php
│
├───Mail
│       WelcomeEmail.php
│
├───Models
│       Audit.php
│       Feature.php
│       FeatureUser.php
│       Profile.php
│       User.php
│
├───Policies
│       UserPolicy.php
│
├───Providers
│       AppServiceProvider.php
│       AuthServiceProvider.php
│       DomainServiceProvider.php
│       ExceptionServiceProvider.php
│       RepositoryServiceProvider.php
│       RouteServiceProvider.php
│
└───Repositories
    ├───Eloquent
    │       EloquentAuditRepository.php
    │       EloquentProfileRepository.php
    │       EloquentUserRepository.php
    │
    └───Interfaces
            AuditRepositoryInterface.php
            ProfileRepositoryInterface.php
            UserRepositoryInterface.php



Analise a estrutura atual de “User” e seus arquivos relacionados (UserService, UserController, UserRepository e AuditService).
Identifique em cada um desses arquivos quais eram as intenções originais de uso que a IA havia recomendado (além do log).
Explique de que forma esses artefatos já existentes devem ser usados para cobrir todas as responsabilidades do módulo de usuário (sem criar arquivos ou classes novos).
Pontos a esclarecer
Em cada arquivo existente (UserService, UserController, UserRepository, AuditService), quais funcionalidades ou comportamentos a IA pretendia abranger?
Como cada um desses arquivos se relaciona ao fluxo completo de criação, edição, exclusão e autenticação de usuários?
Há alguma funcionalidade sugerida inicialmente (por exemplo, eventos, notificações, validações extras, tratamento de exceções, etc.) que já deva estar contemplada em um desses arquivos existentes? Se sim, explique como e onde encaixá-la.
===
me ajude a integrar os arquivos abaixo, mas primeiro, leia o arquivo como está e implemente da melhor forma possivel com qual a interação recomendada dele com userController:
#file:UserController.php integrado com:
#file:RateLimitService.php #file:FeatureFlagService.php

===


um agente de ia é um programa o qual entende o ambiente(contexto) através de sensores como: APIs, base de dados, câmera, microfone. E utiliza atuadores como: mandar email, pesquisar tal coisa. Tudo com objetivo de proatividade(pouca necessidade de intervenção humana)
exemplos podem ser: o robô de limpeza autônomo, que entende o contexto da casa (mapeia paredes, cantos com sensores) e age limpando a casa sem necessidade de intervenção humana


RAG é uma estratégia para obter conteúdo atualizado, é muito mais vantajoso usar ele que ter que ficar treinando o LLM para ele responder cada conteúdo
o problema dele é que se a fonte estiver com informações erradas, o modelo trará as informações erradas
*?como ele funciona:
ele pega o prompt do usuário e dá uma melhorada com as informações das fontes que ele escolheu para aquele contexto
e executa o novo prompt
isso aumenta a precisão então consequentemente diminui as alucinações
? útil por exemplo um chatbot que responde com a base de dados específica da empresa


Random forest
ele é um algoritmo de arvore de escolha; ele pega as fontes e colocas as informações similares em várias árvores (grafos). A fase de escolha é quando o algoritmo escolhe aleatóriamente os próximos galhos que combinam mais com os anteriores, *fazem isso com cada árvore e combinam os resultados em uma resposta final

A vantagem é a precisão e reduz o "overfitting" que é quando o modelo responde apenas com a base de dados treinada
a desvantagem é que é trabalhoso analisar todas as árvores de decisões tomadas
um exemplo disso é que quando o trabalho é feito com sistemas que tem o objetivo de precisão como: contador de crédito
esses sistemas não precisam de uma interpretação boa

CNN
são algoritmos de análise de imagem, especialmente imagens com pixels bem definidos
suas camadas são:
análise de estrutura: bordas *e texturas são identificadas
pooling: redução dos pixeis considerados irrelevantes, para focar no principal da imagem
fazem isso até entender os objetos complexos
eles usam as estratégias acima para aprender características da imagem
um exemplo de uso desses algoritmos são: faceID, diagnostico médico para identifcar tumores, ou erros em radiografias, carros autonomos ( que identificam faixa de pedestres, pessoas, placas)

===


## **UserController** - Camada de Apresentação
Além do log com `AuditService`, o controller deve integrar:

- **Feature Flags**: Integrar com `FeatureFlagService` para funcionalidades experimentais
- **Validação**: Usar `StoreUserRequest` e `UpdateUserRequest`
- **Autorização**: Aplicar a `UserPolicy` para controle de acesso
- **Recursos de API**: Retornar dados via `UserResource`

## **UserService** - Lógica de Negócio
O serviço deve orquestrar múltiplos componentes:

- **Commands/Queries**: Usar os commands `CreateUserCommand`, `UpdateUserCommand`, `DeleteUserCommand` e queries `GetUserByIdQuery`, `GetUserByEmailQuery`
- **Cache**: Integrar `CacheService` para otimizar consultas frequentes
- **Notificações**: Usar `NotificationService` para eventos importantes
- **Jobs Assíncronos**: Disparar `ProcessUserRegistration` e `SendWelcomeEmailJob`
- **Profile Integration**: Trabalhar com `ProfileService` via `ProfileDTO`

## **UserRepository** - Persistência
Além das operações CRUD básicas:

- **Batch Processing**: Integrar com `BatchProcessorService` para operações em lote
- **Export**: Usar `ExportService` para relatórios
- **Monitoring**: Aplicar `MonitoringService` para métricas de performance

## **Fluxo Completo de Usuário**

### **Criação de Usuário:**
1. `UserController` recebe request via `StoreUserRequest`
2. Aplica `UserPolicy` e `RateLimitService`
3. `UserService` executa `CreateUserCommand`
4. Dispara `ProcessUserRegistration` job
5. Job executa `SendWelcomeEmailJob` com `WelcomeEmail`
6. `AuditService` registra a ação

### **Autenticação:**
- `AuthController` usa `AuthService`
- `LoginCommand` processa login
- `GetCurrentUserQuery` obtém usuário logado

### **Funcionalidades Especiais:**

**Feature Flags**: O `FeatureFlagService` deve ser usado para:
- Ativar/desativar funcionalidades por usuário via `FeatureUser`
- Controlar acesso a recursos experimentais

**Webhooks**: O `WebhookService` deve ser configurado para:
- Notificar sistemas externos sobre eventos de usuário
- Integrar com `WebhookController`

**File Storage**: O `FileStorageService` pode ser usado para:
- Upload de avatares de usuário
- Armazenamento de documentos do perfil

**Queue Management**: O `QueueManagerService` deve monitorar:
- Jobs de processamento de usuário
- Performance das filas

A estrutura sugere um sistema robusto onde cada componente tem responsabilidades bem definidas, seguindo padrões como CQRS (Commands/Queries), Repository Pattern, e Event-Driven Architecture através de Jobs e Notifications.

===


//GET {{base_url}}/users?page=4
response: {
    "success": true,
    "message": "Users retrieved successfully",
    "data": [
        {
            "id": 46,
            "name": "Nya Herman",
            "email": "krajcik.aidan@example.org",
            "email_verified_at": "2025-05-24T23:00:55.000000Z",
            "created_at": "2025-05-24T23:00:55.000000Z",
            "updated_at": "2025-05-24T23:00:55.000000Z"
        },
        {
            "id": 47,
            "name": "Nicole Spencer Sr.",
            "email": "lenora11@example.com",
            "email_verified_at": "2025-05-24T23:00:55.000000Z",
            "created_at": "2025-05-24T23:00:55.000000Z",
            "updated_at": "2025-05-24T23:00:55.000000Z"
        },
        {
            "id": 48,
            "name": "Mohammed O'Hara",
            "email": "zpaucek@example.com",
            "email_verified_at": "2025-05-24T23:00:55.000000Z",
            "created_at": "2025-05-24T23:00:55.000000Z",
            "updated_at": "2025-05-24T23:00:55.000000Z"
        },
        {
            "id": 49,
            "name": "Robert Rosenbaum",
            "email": "ydaniel@example.com",
            "email_verified_at": "2025-05-24T23:00:55.000000Z",
            "created_at": "2025-05-24T23:00:55.000000Z",
            "updated_at": "2025-05-24T23:00:55.000000Z"
        },
        {
            "id": 50,
            "name": "Jaunita Keebler",
            "email": "laurine.nitzsche@example.com",
            "email_verified_at": "2025-05-24T23:00:55.000000Z",
            "created_at": "2025-05-24T23:00:55.000000Z",
            "updated_at": "2025-05-24T23:00:55.000000Z"
        },
        {
            "id": 51,
            "name": "Ruben Stamm",
            "email": "braulio86@example.net",
            "email_verified_at": "2025-05-24T23:00:55.000000Z",
            "created_at": "2025-05-24T23:00:55.000000Z",
            "updated_at": "2025-05-24T23:00:55.000000Z"
        },
        {
            "id": 52,
            "name": "João Silva",
            "email": "joao.silva@exemplo.com",
            "email_verified_at": null,
            "created_at": "2025-05-25T21:03:04.000000Z",
            "updated_at": "2025-05-25T21:03:04.000000Z"
        },
        {
            "id": 58,
            "name": "João Silva Santos",
            "email": "joao.silva2@exemplo.com",
            "email_verified_at": null,
            "created_at": "2025-05-26T03:35:35.000000Z",
            "updated_at": "2025-06-06T16:00:20.000000Z"
        }
    ],
    "timestamp": "2025-06-06T17:16:02.554466Z"
}

//GET {{base_url}}/users/58
response: {
    "success": true,
    "message": "User retrieved successfully",
    "data": {
        "id": 58,
        "name": "João Silva Santos",
        "email": "joao.silva2@exemplo.com",
        "email_verified_at": null,
        "created_at": "2025-05-26T03:35:35.000000Z",
        "updated_at": "2025-06-06T16:00:20.000000Z",
        "profile": {
            "id": 58,
            "user_id": 58,
            "phone": "(11) 98765-4321",
            "address": null,
            "city": null,
            "state": null,
            "zip_code": null,
            "avatar": null,
            "bio": "Desenvolvedor Senior Full Stack especializado em Laravel, Vue.js e React. 8 anos de experiência em desenvolvimento de APIs RESTful e arquiteturas escaláveis.",
            "created_at": "2025-05-26T03:35:35.000000Z",
            "updated_at": "2025-06-06T16:00:20.000000Z"
        }
    },
    "timestamp": "2025-06-06T16:48:28.619038Z"
}

//POST {{base_url}}/users
body: {
    "name": "João Silva",
    "email": "mmurilocarazatoo@gmail.com",
    "password": "senha123456",
    "password_confirmation": "senha123456",
    "profile": {
        "bio": "Desenvolvedor Full Stack com 5 anos de experiência em Laravel e Vue.js. Apaixonado por tecnologia e sempre buscando aprender novas ferramentas.",
        "phone": "(11) 99999-9999"
    }
}

response: {
    "success": true,
    "message": "User created successfully",
    "data": {
        "id": 61,
        "name": "João Silva",
        "email": "mmurilocarazatoo@gmail.com",
        "email_verified_at": null,
        "created_at": "2025-06-06T17:17:58.000000Z",
        "updated_at": "2025-06-06T17:17:58.000000Z",
        "profile": {
            "id": 61,
            "user_id": 61,
            "phone": "(11) 99999-9999",
            "address": null,
            "city": null,
            "state": null,
            "zip_code": null,
            "avatar": null,
            "bio": "Desenvolvedor Full Stack com 5 anos de experiência em Laravel e Vue.js. Apaixonado por tecnologia e sempre buscando aprender novas ferramentas.",
            "created_at": "2025-06-06T17:17:58.000000Z",
            "updated_at": "2025-06-06T17:17:58.000000Z"
        }
    },
    "timestamp": "2025-06-06T17:17:58.814541Z"
}

//PUT {{base_url}}/users/58
body: {
    "name": "João Silva Santos",
    "email": "joao.silva@exemplo.com",
    "password": "novaSenha123",
    "password_confirmation": "novaSenha123",
    "profile": {
        "bio": "Desenvolvedor Senior Full Stack especializado em Laravel, Vue.js e React. 8 anos de experiência em desenvolvimento de APIs RESTful e arquiteturas escaláveis.",
        "phone": "(11) 98765-4321"
    }
}

response: {
    "success": true,
    "message": "User updated successfully",
    "data": {
        "id": 61,
        "name": "João Silva Santos",
        "email": "joao.silva3@exemplo.com",
        "email_verified_at": null,
        "created_at": "2025-06-06T17:17:58.000000Z",
        "updated_at": "2025-06-06T17:18:48.000000Z"
    },
    "timestamp": "2025-06-06T17:18:48.848885Z"
}

//DELETE {{base_url}}/users/59
response: {
    "success": true,
    "message": "User deleted successfully",
    "data": [],
    "timestamp": "2025-06-06T16:01:13.237408Z"
}