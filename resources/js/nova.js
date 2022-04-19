import IndexField from './nova/IndexField'
import DetailField from './nova/DetailField'
import FormField from './nova/FormField'

Nova.booting((Vue, router, store) => {
  /* eslint-disable vue/component-definition-name-casing */
  /* Nova expects names in this format */
  Vue.component('index-eloquent-imagery', IndexField)
  Vue.component('detail-eloquent-imagery', DetailField)
  Vue.component('form-eloquent-imagery', FormField)
  /* eslint-enable */
})
