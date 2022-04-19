let mix = require('laravel-mix')

mix.setPublicPath('dist/')
  .js('resources/js/nova.js', 'js')
  .options({ terser: { extractComments: false } })
  .vue()

if (!mix.inProduction()) {
  mix.sourceMaps()
}
