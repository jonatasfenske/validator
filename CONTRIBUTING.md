# Contribuindo com Validator

Obrigado pelo interesse em contribuir! Este documento descreve as diretrizes para enviar contribuições ao componente **Validator** (`validator`).

## Como Contribuir

### Reportando Bugs

1. Verifique se o bug já foi reportado nas [Issues](https://github.com/jonatasfenske/validator/issues)
2. Se não encontrado, abra uma nova issue com:
   - Descrição clara do problema
   - Passos para reproduzir
   - Versão do PHP (`php -v`)
   - Stack trace completo (se aplicável)

### Sugerindo Melhorias

1. Abra uma issue com o label `enhancement`
2. Descreva o caso de uso e o benefício esperado
3. Se possível, inclua exemplos de código desejado

### Enviando Pull Requests

1. Fork o repositório
2. Crie uma branch a partir de `main`:
   ```bash
   git checkout -b feat/nome-do-recurso
   # ou
   git checkout -b fix/descricao-do-bugfix
   ```
3. Implemente suas mudanças seguindo as convenções abaixo
4. Adicione testes para novos recursos ou correções
5. Execute a suíte completa antes de submeter:
   ```bash
   composer test
   composer analyse
   ```
6. Commit com mensagem descritiva (Conventional Commits):
   ```bash
   git commit -m "feat(validator): adicionar novo recurso"
   git commit -m "fix(validator): corrigir comportamento inesperado"
   ```
7. Push e abra o Pull Request

## Convenções de Código

### PHP

- **PHP 8.5+** obrigatório — use recursos modernos da linguagem
- `declare(strict_types=1)` no topo de todos os arquivos
- Tipagem estrita em parâmetros, retornos e propriedades
- Enums backed preferidos sobre constantes string/int
- Readonly classes para value objects imutáveis
- Named arguments em APIs públicas
- PHPDoc completo em métodos públicos e protegidos
- Sem anotações JetBrains — use PHPDoc padrão

### Nomenclatura

| Elemento | Convenção | Exemplo |
|---|---|---|
| Classes | PascalCase | `ValidatorConfig` |
| Métodos | camelCase | `configure` |
| Propriedades | camelCase | `$cachePath` |
| Constantes | UPPER_SNAKE | `METHOD_NOT_ALLOWED` |
| Namespaces | PascalCase | `JonatasFenske\Validator` |
| Arquivos | PascalCase (mesmo nome da classe) | `ValidatorConfig.php` |
| Traits | PascalCase com prefixo Has/Use | `HasAttributes` |

### Testes

- Um arquivo de teste por classe/recurso: `{ClassName}Test.php`
- Métodos: `test{DescriçãoDoComportamento}` em camelCase
- Use AAA pattern (Arrange-Act-Assert)
- Cubra cenários positivos E negativos
- Testes devem ser independentes e isolados
- Arquivos temporários em `sys_get_temp_dir()`, limpos no `tearDown()`

## Estrutura do Componente

```
src/       # Código-fonte (JonatasFenske\Validator)
tests/     # Testes espelhando a estrutura de src/
examples/  # Exemplos executáveis
```

## Processo de Review

1. Todos os PRs requerem revisão antes do merge
2. CI deve passar (testes + análise estática)
3. Mudanças breaking requerem discussão prévia em issue
4. Novos recursos devem incluir testes e documentação

## Licença

Ao contribuir, você concorda que suas contribuições serão licenciadas sob a licença MIT do projeto.

---

*Feito com ☕ pela comunidade PHP brasileira*