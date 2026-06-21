# Fluxo de trabalho no GitHub — Projeto S.O.P.A.

Guia prático para o grupo (6 pessoas) trabalhar no mesmo repositório sem pisar no
trabalho um do outro. Modelo usado: **GitHub Flow** — simples, só uma branch
principal (`main`) sempre funcional, e uma branch nova para cada tarefa.

## 1. Organização inicial (fazer uma vez só)

1. Uma pessoa do grupo cria o repositório no GitHub e sobe o código atual
   (o `index.html` e a pasta `CSS/` que já existem).
2. Essa pessoa vai em **Settings → Collaborators** e adiciona os outros 5
   pelo usuário do GitHub.
3. (Recomendado) Em **Settings → Branches**, proteger a `main` marcando
   _"Require a pull request before merging"_. Isso impede que alguém suba
   código direto na `main` sem ninguém revisar — evita que um erro de um
   derrube a parte de todo mundo.
4. Cada um clona o repositório na própria máquina:
   ```bash
   git clone https://github.com/SEU-GRUPO/sopa.git
   ```

## 2. Como dividir o trabalho

Pelas telas que vocês já desenharam, dá pra dividir assim — cada pessoa fica
"dona" de uma página/parte, o que praticamente elimina conflito de arquivo:

| Pessoa | Parte do sistema                                                                       |
| ------ | -------------------------------------------------------------------------------------- |
| 1      | Página inicial (home) — já começada                                                    |
| 2      | Página "Quem somos"                                                                    |
| 3      | Cadastro                                                                               |
| 4      | Login                                                                                  |
| 5      | Dashboard / tela de pedido (área logada)                                               |
| 6      | Componentes compartilhados (header, footer, variáveis de cor/fonte) e integração geral |

Dica: criem um **Project (quadro Kanban)** no GitHub — aba _Projects_ do
repositório — com colunas "A fazer", "Fazendo" e "Pronto", e um **Issue**
(tarefa) para cada página. Assim todo mundo vê quem está com o quê.

### Evitar conflito no CSS

Com 6 pessoas mexendo no mesmo `style.css`, esse arquivo vira o principal
ponto de conflito. Duas saídas simples:

- **Separar por arquivo** (mais simples de revisar): `CSS/base.css` (cores,
  fontes, header, footer — reset compartilhado) + um arquivo por página
  (`CSS/home.css`, `CSS/login.css`, `CSS/cadastro.css`...). Cada um só mexe
  no seu próprio arquivo.
- Se preferirem manter um CSS único, então: avisem no grupo antes de mexer
  em algo que não é "seu" trecho, e façam `git pull` com frequência.

## 3. Rotina do dia a dia (cada tarefa)

```bash
# 1. Sempre comece atualizando a main local
git checkout main
git pull origin main

# 2. Crie uma branch só sua para a tarefa
git checkout -b feature/login-derek

# 3. Trabalhe e vá commitando aos poucos (não espere terminar tudo)
git add .
git commit -m "feat: estrutura do formulário de login"

# 4. Envie sua branch pro GitHub
git push origin feature/login-derek
```

Depois, no site do GitHub: abra um **Pull Request** da sua branch para a
`main`, escreva 2 linhas do que fez, e marque um colega para revisar (ele só
precisa dar uma olhada e clicar em "Approve"). Depois disso, clique em
**Merge** (use a opção _"Squash and merge"_ — deixa o histórico mais limpo)
e apague a branch.

### Padrão de nome de branch

`feature/parte-pessoa` → ex.: `feature/cadastro-gabriel`,
`feature/dashboard-miguel`.

### Padrão de mensagem de commit

- `feat:` algo novo (`feat: cria seção sobre nós`)
- `fix:` correção de bug
- `style:` ajuste visual/CSS sem mudar funcionalidade
- `docs:` documentação (README, este arquivo, etc.)

## 4. Se der conflito

Vai acontecer eventualmente, é normal. Quando o Git avisar de conflito:

1. Abra os arquivos marcados — o Git mostra `<<<<<<<`, `=======`, `>>>>>>>`
   ao redor do trecho conflitante.
2. Decidam juntos (rapidinho, no grupo do WhatsApp/Discord) qual versão
   fica, apaguem as marcações e deixem o código correto.
3. `git add .` → `git commit` → `git push` de novo.

O **VS Code** tem um editor visual de conflitos (botões "Accept Current /
Accept Incoming / Accept Both") que facilita bastante — mais simples que
resolver no terminal puro.

## 5. Ferramentas que ajudam (vocês não precisam usar só linha de comando)

- **VS Code**: aba "Source Control" já mostra mudanças, permite commit/push
  com clique, e mostra o diff de cada arquivo.
- **GitHub Desktop**: interface gráfica simples, boa para quem está
  começando com Git agora.

## 6. Checklist rápido antes de qualquer Pull Request

- [ ] Atualizei minha branch com a `main` mais recente antes de abrir o PR
- [ ] Testei a página abrindo o `index.html` (ou a página correspondente) no navegador
- [ ] Não deixei `console.log`, comentário de teste ou código comentado sobrando
- [ ] Descrevi em 1–2 linhas o que o PR faz
