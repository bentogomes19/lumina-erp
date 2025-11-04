# Banco de Dados Lumina ERP

O banco de dados do sistema é composto por 14 entidades 

| Tabela            | PT-BR                   | Definição                                                                                   |
|-------------------|-------------------------|---------------------------------------------------------------------------------------------|
| Assessment        | Avaliação               | Representa uma avaliação aplicada em uma turma/disciplina (prova, trabalho, atividade etc.). |
| Attendance        | Presença                | Registra a presença ou falta do aluno em uma aula, dia letivo ou evento escolar específico. |
| Enrollment        | Matrícula do aluno      | Vínculo do aluno com uma turma e ano letivo, incluindo status da matrícula.                 |
| Grade             | Notas                   | Resultado (nota/conceito) obtido pelo aluno em uma avaliação ou componente curricular.      |
| GradeLevel        | Nível de escolaridade   | Segmento/série do ensino (ex.: 6º ano, 9º ano, 1ª série EM), usado para organizar turmas.   |
| Permission        | Permissões              | Ação específica que pode ser liberada ou bloqueada no sistema (ex.: criar, editar, visualizar). |
| Role              | Papel (Perfil)          | Conjunto de permissões agrupadas atribuídas a usuários (ex.: Admin, Professor, Secretaria). |
| SchoolClass       | Turmas                  | Turma escolar em um ano letivo, ligada a um nível de escolaridade e turno.                  |
| Student           | Alunos                  | Representa o aluno, com dados pessoais e acadêmicos básicos.                                |
| Subject           | Disciplinas             | Disciplina ofertada pela escola (ex.: Matemática, Português), ligada a um nível/série.      |
| Teacher           | Professores             | Representa o professor, com dados pessoais e vínculo com a instituição.                     |
| TeacherAssignment | Atribuição do Professor | Liga professor, turma e disciplina em um ano letivo, definindo o que ele leciona para quem. |
| User              | Usuários                | Conta de acesso ao sistema (login), podendo estar ligada a aluno, professor ou staff.       |


---

## Entidade: `Assessment`

Representa uma avaliação aplicada em uma turma/disciplina (prova, trabalho, atividade etc.).

| Campo       | Tipo             | Descrição                                                                                           |
|-------------|------------------|-----------------------------------------------------------------------------------------------------|
| id          | bigint unsigned  | Identificador único da avaliação.                                                                   |
| title       | varchar(120)     | Título da avaliação (ex.: “Prova Bimestral de Matemática”, “Trabalho de História”).                |
| scheduled_at| datetime         | Data e hora em que a avaliação está agendada para ocorrer.                                         |
| weight      | decimal(3,1)     | Peso da avaliação na composição da nota final (ex.: 2.0, 3.5, 1.0).                                |
| description | text             | Descrição detalhada da avaliação, orientações ou critérios de correção.                           |
| date        | date             | Data referência da avaliação (por exemplo, data letiva ou de lançamento).                         |
| class_id    | bigint unsigned  | Referência à turma (`school_classes.id`) para a qual a avaliação foi criada.                      |
| subject_id  | bigint unsigned  | Referência à disciplina (`subjects.id`) à qual a avaliação pertence.                               |
| teacher_id  | bigint unsigned  | Referência ao professor (`teachers.id`) responsável pela avaliação.                                |
| created_at  | timestamp        | Data e hora em que o registro da avaliação foi criado no sistema.                                  |
| updated_at  | timestamp        | Data e hora da última atualização do registro da avaliação.                                        |


---


## Entidade: `Atttendance`
Registra a presença ou falta do aluno em uma aula, dia letivo ou evento escolar específico.

| Campo      | Tipo             | Descrição                                                                                 |
|-----------|------------------|-------------------------------------------------------------------------------------------|
| id        | bigint unsigned  | Identificador único do registro de presença.                                              |
| student_id| bigint unsigned  | Referência ao aluno (`students.id`) ao qual a presença/falta se refere.                  |
| class_id  | bigint unsigned  | Referência à turma (`school_classes.id`) em que a presença foi registrada.               |
| subject_id| bigint unsigned  | Referência à disciplina (`subjects.id`) relacionada à aula. Pode ser nulo em alguns cenários. |
| date      | date             | Data da aula/registro de presença.                                                       |
| status    | varchar(16)      | Situação da presença na data (ex.: **presente**, **falta**, **atraso**, conforme enum usado). |
| created_at| timestamp        | Data e hora em que o registro foi criado no sistema.                                     |
| updated_at| timestamp        | Data e hora da última atualização do registro.                                           |

---

## Entidade: `Enrollment`

| Campo           | Tipo                                        | Descrição                                                                                                 |
|-----------------|---------------------------------------------|-----------------------------------------------------------------------------------------------------------|
| id              | bigint unsigned                             | Identificador único da matrícula.                                                                         |
| student_id      | bigint unsigned                             | Referência ao aluno (`students.id`) vinculado a esta matrícula.                                           |
| class_id        | bigint unsigned                             | Referência à turma (`school_classes.id`) na qual o aluno está matriculado.                                |
| enrollment_date | date                                        | Data em que a matrícula do aluno na turma foi efetivada.                                                 |
| roll_number     | int                                         | Número de chamada / identificação do aluno dentro da turma.                                               |
| status          | enum('Ativa','Suspensa','Cancelada','Completa') | Situação atual da matrícula: **Ativa**, **Suspensa**, **Cancelada** ou **Completa** (ano concluído).      |
| created_at      | timestamp                                   | Data e hora em que o registro da matrícula foi criado no sistema.                                        |
| updated_at      | timestamp                                   | Data e hora da última atualização do registro da matrícula.                                              |


## Entidade: Grade

| Campo           | Tipo                                                                                     | Descrição                                                                                                                                |
|-----------------|------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------|
| id              | bigint unsigned                                                                          | Identificador único da nota/lançamento de avaliação.                                                                                     |
| enrollment_id   | bigint unsigned                                                                          | Referência à matrícula do aluno (`enrollments.id`) à qual esta nota está vinculada.                                                     |
| student_id      | bigint unsigned                                                                          | Referência direta ao aluno (`students.id`) para facilitar consultas e relatórios.                                                       |
| class_id        | bigint unsigned                                                                          | Referência à turma (`school_classes.id`) em que a nota foi registrada.                                                                  |
| subject_id      | bigint unsigned                                                                          | Referência à disciplina (`subjects.id`) correspondente à nota.                                                                          |
| term            | enum('b1','b2','b3','b4')                                                                | Período/etapa letiva a que a nota pertence (ex.: b1 = 1º bimestre, b2 = 2º bimestre, etc.).                                            |
| assessment_type | enum('test','quiz','work','project','participation','recovery')                         | Tipo da avaliação: prova, quiz, trabalho, projeto, participação ou nota de recuperação.                                                |
| sequence        | tinyint unsigned                                                                         | Ordem/sequência da avaliação dentro do bimestre ou disciplina (ex.: 1ª prova, 2º trabalho).                                             |
| teacher_id      | bigint unsigned                                                                          | Referência ao professor (`teachers.id`) que lançou ou é responsável pela avaliação.                                                    |
| score           | decimal(5,2)                                                                             | Nota obtida pelo aluno na avaliação.                                                                                                    |
| max_score       | decimal(5,2)                                                                             | Nota máxima possível para essa avaliação (ex.: 10,00; 100,00).                                                                         |
| weight          | decimal(4,2)                                                                             | Peso da avaliação no cálculo da média do período/disciplina.                                                                           |
| comment         | text                                                                                     | Observações adicionais sobre a nota (comentários do professor, justificativas, etc.).                                                  |
| posted_by       | bigint unsigned                                                                          | Referência ao usuário (`users.id`) que efetuou o lançamento da nota no sistema.                                                        |
| locked_at       | timestamp                                                                                | Data e hora em que o lançamento foi “travado”, impedindo novas alterações (fechamento do diário/bimestre).                            |
| origin          | enum('manual','import')                                                                  | Origem do lançamento: manual (via sistema) ou importação (planilha, integração externa, etc.).                                         |
| recovery_of_id  | bigint unsigned                                                                          | Referência à nota original (`grades.id`) que está sendo substituída/ajustada por uma avaliação de recuperação, se aplicável.          |
| date_recorded   | date                                                                                     | Data em que a nota foi efetivamente registrada como válida (data de lançamento ou de conclusão da avaliação).                          |
| created_at      | timestamp                                                                                | Data e hora em que o registro de nota foi criado no sistema.                                                                           |
| updated_at      | timestamp                                                                                | Data e hora da última atualização do registro de nota.                                                                                 |


## Entidade: GradeLevel

| Campo         | Tipo             | Descrição                                                                                           |
|---------------|------------------|-----------------------------------------------------------------------------------------------------|
| id            | bigint unsigned  | Identificador único do nível de escolaridade.                                                       |
| name          | varchar(255)     | Nome do nível de escolaridade (ex.: “6º ano”, “9º ano”, “1ª série do Ensino Médio”).               |
| stage         | varchar(255)     | Etapa/segmento ao qual o nível pertence (ex.: “Fundamental I”, “Fundamental II”, “Ensino Médio”).  |
| display_order | smallint unsigned| Ordem de exibição usada para organizar os níveis em listagens e telas (do menor para o maior nível).|
| description   | text             | Descrição opcional do nível, incluindo observações pedagógicas ou critérios de uso.               |
| created_at    | timestamp        | Data e hora em que o nível foi criado no sistema.                                                  |
| updated_at    | timestamp        | Data e hora da última atualização do registro do nível.                                            |


## Entidade: Permission

| Campo      | Tipo             | Descrição                                                                                       |
|------------|------------------|-------------------------------------------------------------------------------------------------|
| id         | bigint unsigned  | Identificador único da permissão.                                                               |
| name       | varchar(255)     | Nome interno da permissão (ex.: `view_students`, `edit_enrollments`).                          |
| guard_name | varchar(255)     | Contexto/guard de autenticação ao qual a permissão pertence (ex.: `web`, `api`).              |
| created_at | timestamp        | Data e hora em que a permissão foi criada no sistema.                                          |
| updated_at | timestamp        | Data e hora da última atualização do registro da permissão.                                    |


## Entidade: Role

| Campo      | Tipo             | Descrição                                                                                      |
|------------|------------------|------------------------------------------------------------------------------------------------|
| id         | bigint unsigned  | Identificador único do papel/perfil.                                                           |
| name       | varchar(255)     | Nome do papel (ex.: `admin`, `teacher`, `student`, `secretary`).                              |
| guard_name | varchar(255)     | Contexto/guard de autenticação ao qual o papel pertence (ex.: `web`, `api`).                  |
| created_at | timestamp        | Data e hora em que o papel foi criado no sistema.                                             |
| updated_at | timestamp        | Data e hora da última atualização do registro do papel.                                       |


## Entidade: SchoolClass

| Campo              | Tipo             | Descrição                                                                                                      |
|--------------------|------------------|----------------------------------------------------------------------------------------------------------------|
| id                 | bigint unsigned  | Identificador único da turma.                                                                                  |
| uuid               | char(36)         | Identificador universal único da turma (UUID), usado para integrações e referências externas.                 |
| name               | varchar(255)     | Nome descritivo da turma (ex.: “6º Ano A”, “3ª Série B – Manhã”).                                             |
| code               | varchar(20)      | Código interno da turma (ex.: “6A-MAN-2025”), usado para identificação rápida e relatórios.                   |
| shift              | varchar(32)      | Turno da turma (ex.: “Matutino”, “Vespertino”, “Noturno”), de acordo com o enum/configuração do sistema.      |
| homeroom_teacher_id| bigint unsigned  | Referência ao professor responsável/orientador da turma (`teachers.id`), quando houver.                       |
| capacity           | int              | Capacidade máxima de alunos na turma.                                                                          |
| status             | varchar(32)      | Situação da turma (ex.: “Ativa”, “Inativa”, “Encerrada”), conforme enum/regra de negócio definida.            |
| type               | varchar(32)      | Tipo da turma (ex.: “Regular”, “Recuperação”, “Eletiva”), conforme enum/regra de negócio definida.            |
| created_at         | timestamp        | Data e hora em que o registro da turma foi criado no sistema.                                                 |
| updated_at         | timestamp        | Data e hora da última atualização do registro da turma.                                                       |
| deleted_at         | timestamp        | Data e hora da exclusão lógica (soft delete) da turma, quando aplicável.                                      |
| grade_level_id     | bigint unsigned  | Referência ao nível de escolaridade (`grade_levels.id`) ao qual a turma pertence.                             |
| school_year_id     | bigint unsigned  | Referência ao ano letivo (`school_years.id`) em que a turma está configurada.                                 |


## Entidade: Student

| Campo             | Tipo                                                              | Descrição                                                                                                                     |
|-------------------|-------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------|
| id                | bigint unsigned                                                   | Identificador único do aluno.                                                                                                 |
| uuid              | char(36)                                                          | Identificador universal único (UUID) do aluno, utilizado para integrações e referências externas.                            |
| user_id           | bigint unsigned                                                   | Referência ao usuário de acesso (`users.id`) vinculado ao aluno (conta de login).                                            |
| registration_number | varchar(255)                                                    | Número de registro/matrícula institucional do aluno (ex.: RA, código interno da escola).                                     |
| name              | varchar(255)                                                      | Nome completo do aluno.                                                                                                       |
| birth_date        | date                                                              | Data de nascimento do aluno.                                                                                                  |
| gender            | varchar(255)                                                      | Gênero do aluno, conforme convenção adotada pela escola/sistema.                                                             |
| cpf               | varchar(255)                                                      | CPF do aluno (quando aplicável).                                                                                              |
| rg                | varchar(20)                                                       | Documento de identidade (RG) ou equivalente, quando utilizado.                                                               |
| email             | varchar(255)                                                      | E-mail de contato do aluno (ou responsável, conforme a política da instituição).                                             |
| phone_number      | varchar(255)                                                      | Telefone principal de contato do aluno.                                                                                      |
| address           | varchar(255)                                                      | Endereço (logradouro) de residência do aluno.                                                                                |
| city              | varchar(255)                                                      | Cidade de residência do aluno.                                                                                               |
| state             | varchar(255)                                                      | Estado (UF/Região) de residência do aluno.                                                                                   |
| postal_code       | varchar(255)                                                      | CEP do endereço do aluno.                                                                                                     |
| mother_name       | varchar(255)                                                      | Nome da mãe ou responsável materno, para registros escolares.                                                                |
| father_name       | varchar(255)                                                      | Nome do pai ou responsável paterno, para registros escolares.                                                                |
| status            | enum('active','inactive','suspended','graduated')                | Situação do aluno no sistema: ativo, inativo, suspenso ou egresso/concluído (graduated).                                     |
| enrollment_date   | date                                                              | Data da primeira matrícula do aluno na instituição (ou na unidade atual).                                                   |
| exit_date         | date                                                              | Data de saída do aluno da instituição, quando houver (transferência, conclusão, cancelamento definitivo).                    |
| meta              | json                                                              | Campo flexível para armazenar informações adicionais em formato JSON (ex.: observações específicas, configurações extras).   |
| created_at        | timestamp                                                         | Data e hora em que o registro do aluno foi criado no sistema.                                                                |
| updated_at        | timestamp                                                         | Data e hora da última atualização do registro do aluno.                                                                      |
| deleted_at        | timestamp                                                         | Data e hora da exclusão lógica (soft delete) do registro do aluno, quando aplicável.                                        |
| address_district  | varchar(255)                                                      | Bairro do endereço de residência do aluno.                                                                                   |
| birth_city        | varchar(255)                                                      | Cidade de nascimento do aluno.                                                                                               |
| birth_state       | varchar(2)                                                        | UF do estado de nascimento do aluno.                                                                                         |
| nationality       | varchar(255)                                                      | Nacionalidade do aluno.                                                                                                      |
| guardian_main     | varchar(255)                                                      | Nome do responsável principal pelo aluno (quando diferente dos pais ou para fins de contato oficial).                        |
| guardian_phone    | varchar(255)                                                      | Telefone do responsável principal.                                                                                           |
| guardian_email    | varchar(255)                                                      | E-mail do responsável principal.                                                                                             |
| transport_mode    | enum('none','car','bus','van','walk','bike')                     | Meio de transporte utilizado pelo aluno para ir à escola (nenhum, carro, ônibus, van, a pé, bicicleta).                     |
| has_special_needs | tinyint(1)                                                        | Indica se o aluno possui necessidades educacionais especiais (1 = sim, 0 = não).                                             |
| medical_notes     | text                                                              | Informações médicas relevantes (uso de medicamentos, restrições, observações de saúde gerais).                               |
| allergies         | varchar(255)                                                      | Descrição de alergias conhecidas do aluno (alimentos, medicamentos, outros).                                                 |
| status_changed_at | timestamp                                                         | Data e hora da última alteração do status do aluno (ativo, suspenso, graduado, etc.).                                       |
| photo_url         | varchar(255)                                                      | URL ou caminho da foto do aluno utilizada no sistema (identificação em telas, documentos, carteirinhas, etc.).              |


## Entidade: Subject

| Campo              | Tipo                                                                 | Descrição                                                                                                                       |
|--------------------|----------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------|
| id                 | bigint unsigned                                                      | Identificador único da disciplina.                                                                                              |
| code               | varchar(255)                                                         | Código interno da disciplina (ex.: `MAT-6A`, `PORT-9B`), usado para identificação em telas e relatórios.                       |
| normalized_code    | varchar(255)                                                         | Versão normalizada do código (sem acentos/espaços/variações), útil para buscas e integrações.                                 |
| name               | varchar(255)                                                         | Nome da disciplina (ex.: “Matemática”, “Língua Portuguesa”, “História”).                                                       |
| category           | enum('linguagens','matematica','ciencias_da_natureza','ciencias_humanas') | Categoria/área do conhecimento à qual a disciplina pertence, alinhada à BNCC.                                                 |
| description        | text                                                                 | Descrição detalhada da disciplina, objetivos gerais, observações pedagógicas.                                                 |
| status             | enum('active','inactive')                                            | Situação da disciplina no sistema: ativa (pode ser usada em turmas/matrículas) ou inativa (apenas para histórico).            |
| bncc_code          | varchar(255)                                                         | Código de referência da disciplina na BNCC (quando aplicável).                                                                 |
| bncc_reference_url | varchar(255)                                                         | URL para o documento ou trecho da BNCC relacionado à disciplina.                                                               |
| tags               | json                                                                 | Lista de tags em formato JSON (ex.: áreas, palavras-chave, agrupamentos curriculares).                                        |
| created_at         | timestamp                                                            | Data e hora em que a disciplina foi criada no sistema.                                                                         |
| updated_at         | timestamp                                                            | Data e hora da última atualização do registro da disciplina.                                                                   |
| deleted_at         | timestamp                                                            | Data e hora da exclusão lógica (soft delete) da disciplina, quando aplicável.                                                 |


## Entidade: Teacher

| Campo            | Tipo                                      | Descrição                                                                                                             |
|------------------|-------------------------------------------|-----------------------------------------------------------------------------------------------------------------------|
| id               | bigint unsigned                           | Identificador único do professor.                                                                                     |
| uuid             | char(36)                                  | Identificador universal único (UUID) do professor, usado para integrações e rastreio externo.                        |
| user_id          | bigint unsigned                           | Referência ao usuário de acesso (`users.id`) vinculado ao professor (conta de login).                                 |
| cpf              | varchar(14)                               | CPF do professor.                                                                                                     |
| employee_number  | varchar(255)                              | Matrícula funcional / código interno do professor na instituição.                                                    |
| name             | varchar(255)                              | Nome completo do professor.                                                                                           |
| qualification    | varchar(255)                              | Área de formação ou habilitação principal (ex.: Licenciatura em Matemática).                                         |
| academic_title   | varchar(20)                               | Titulação acadêmica (ex.: Graduação, Especialista, Mestre, Doutor), conforme enum/tabela de apoio.                   |
| birth_date       | date                                      | Data de nascimento do professor.                                                                                      |
| gender           | enum('M','F','O')                         | Gênero do professor: Masculino, Feminino ou Outro.                                                                    |
| hire_date        | date                                      | Data de contratação pela instituição (para vínculo administrativo).                                                   |
| admission_date   | date                                      | Data de admissão/ingresso efetivo na escola (pode coincidir ou não com a contratação formal).                        |
| termination_date | date                                      | Data de desligamento do professor, quando houver.                                                                     |
| regime           | varchar(20)                               | Regime de trabalho (ex.: horista, parcial, integral), conforme convenção da escola.                                  |
| weekly_workload  | smallint unsigned                         | Carga horária semanal contratada do professor (em horas).                                                             |
| max_classes      | smallint unsigned                         | Número máximo de turmas/aulas simultâneas que o professor deve assumir, para controle de alocação.                   |
| email            | varchar(255)                              | E-mail principal do professor.                                                                                        |
| phone            | varchar(255)                              | Telefone fixo ou contato adicional.                                                                                   |
| mobile           | varchar(20)                               | Telefone celular do professor.                                                                                        |
| bio              | text                                      | Breve biografia ou resumo profissional/acadêmico do professor.                                                       |
| lattes_url       | varchar(255)                              | URL do currículo Lattes ou página profissional equivalente.                                                           |
| status           | varchar(20)                               | Situação do professor no sistema (ex.: ativo, inativo, afastado), conforme enum/regra interna.                        |
| created_at       | timestamp                                 | Data e hora em que o registro do professor foi criado no sistema.                                                    |
| updated_at       | timestamp                                 | Data e hora da última atualização do registro do professor.                                                           |
| deleted_at       | timestamp                                 | Data e hora da exclusão lógica (soft delete) do registro, quando aplicável.                                          |
| address_street   | varchar(255)                              | Logradouro (rua, avenida, etc.) do endereço do professor.                                                             |
| address_number   | varchar(10)                               | Número do endereço.                                                                                                   |
| address_district | varchar(255)                              | Bairro do endereço do professor.                                                                                      |
| address_city     | varchar(255)                              | Cidade do endereço do professor.                                                                                      |
| address_state    | varchar(2)                                | UF do estado do endereço (ex.: SP, RJ).                                                                              |
| address_zip      | varchar(10)                               | CEP do endereço do professor.                                                                                         |


## Entidade: TeacherAssignment

| Campo      | Tipo            | Descrição                                                                                           |
|------------|-----------------|-----------------------------------------------------------------------------------------------------|
| id         | bigint unsigned | Identificador único da atribuição do professor.                                                     |
| teacher_id | bigint unsigned | Referência ao professor (`teachers.id`) responsável pela disciplina/turma.                         |
| class_id   | bigint unsigned | Referência à turma (`school_classes.id`) em que o professor leciona.                               |
| subject_id | bigint unsigned | Referência à disciplina (`subjects.id`) que o professor ministra nesta turma.                      |
| created_at | timestamp       | Data e hora em que a atribuição foi criada no sistema.                                             |
| updated_at | timestamp       | Data e hora da última atualização da atribuição.                                                   |


## Entidade: User

| Campo           | Tipo                             | Descrição                                                                                                  |
|-----------------|----------------------------------|------------------------------------------------------------------------------------------------------------|
| id              | bigint unsigned                  | Identificador único do usuário.                                                                            |
| uuid            | char(36)                         | Identificador universal único (UUID) do usuário, usado para integrações e rastreio externo.               |
| name            | varchar(255)                     | Nome completo do usuário.                                                                                  |
| email           | varchar(255)                     | Endereço de e-mail usado para login e comunicação.                                                         |
| email_verified_at | timestamp                      | Data e hora em que o e-mail foi verificado, quando aplicável.                                             |
| password        | varchar(255)                     | Hash da senha de acesso do usuário.                                                                       |
| cpf             | varchar(14)                      | CPF do usuário, quando a instituição exige identificação por documento.                                   |
| rg              | varchar(20)                      | Documento de identidade (RG) ou equivalente.                                                               |
| birth_date      | date                             | Data de nascimento do usuário.                                                                            |
| gender          | enum('M','F','O')                | Gênero do usuário: Masculino, Feminino ou Outro.                                                          |
| address         | varchar(255)                     | Logradouro do endereço de residência (rua, avenida, etc.).                                                |
| district        | varchar(255)                     | Bairro do endereço do usuário.                                                                            |
| city            | varchar(255)                     | Cidade do endereço do usuário.                                                                            |
| state           | varchar(2)                       | UF do estado do endereço (ex.: SP, RJ).                                                                   |
| postal_code     | varchar(9)                       | CEP do endereço do usuário.                                                                               |
| phone           | varchar(20)                      | Telefone fixo ou contato secundário.                                                                      |
| cellphone       | varchar(20)                      | Telefone celular principal do usuário.                                                                    |
| avatar          | varchar(255)                     | Caminho ou URL da imagem de avatar/foto do usuário no sistema.                                           |
| active          | tinyint(1)                       | Indica se o usuário está ativo (1) ou inativo/bloqueado (0) para acesso ao sistema.                       |
| last_login_at   | timestamp                        | Data e hora do último acesso/autenticação do usuário.                                                     |
| remember_token  | varchar(100)                     | Token utilizado pelo mecanismo “remember me” de autenticação.                                             |
| created_at      | timestamp                        | Data e hora em que o usuário foi criado no sistema.                                                       |
| updated_at      | timestamp                        | Data e hora da última atualização do registro do usuário.                                                 |
| deleted_at      | timestamp                        | Data e hora da exclusão lógica (soft delete) do usuário, quando aplicável.                                |
