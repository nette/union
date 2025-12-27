# Template & layout lookup

Templates are found by convention; the candidate paths and their order are the only
non-obvious part.

## Action template candidates (`formatTemplateFiles`)

Starting from the presenter's own directory:

- **If there is no `templates/` subdirectory**, the lookup climbs one level; if
  there is still none, the single candidate is `"<dir>/<view>.latte"` (the
  "template next to the presenter" layout).
- **If a `templates/` subdirectory exists**, two candidates are tried, in order:
  1. `"<dir>/templates/<Presenter>/<view>.latte"`
  2. `"<dir>/templates/<Presenter>.<view>.latte"`

`findTemplateFile()` returns the first that `is_file`, else calls `error()` → **404
"Missing template"**. Remember (see lifecycle.md) this resolution happens **lazily
at `sendTemplate`**, via `completeTemplate`, only when the template has no explicit
file.

## Layout candidates (`formatLayoutTemplateFiles`)

- A `$layout` containing a slash is taken as a direct path.
- Otherwise the base name is `<layout>` (default `layout`) and candidates are
  `@<layout>.latte` forms, searched at the presenter directory and then **up the
  module levels** (`substr_count(name, ':')` levels): the `templates/<Presenter>/`,
  `templates/<Presenter>.`, and shared `templates/@<layout>.latte` forms per level.
- `setLayout(false)` disables the layout entirely (returns `null`); an explicitly
  named layout that is not found throws `FileNotFoundException`, while the default
  layout being absent is simply `null` (optional).

## Typed templates

`formatTemplateClass` derives the template class from the presenter/control name:
for a presenter it tries `<Base><Action>Template` then `<Base>Template` (stripping
the `Presenter` suffix); for a control, `<Base>Template` (stripping `Control`). The
class is validated (`is_a(..., Template::class)`) or a `trigger_error` degrades to
the default. `TemplateFactory::createTemplate` instantiates it around a fresh Latte
engine and injects the default variables (`user`, `baseUrl`, `basePath`, `flashes`,
`control`, `presenter`).

The `{templateType}` Latte tag is **not** in this package — it is implemented in
Latte core; here the typing is only the name-derived class plus the
`@property-read <X>Template $template` docblock.
