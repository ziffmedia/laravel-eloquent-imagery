Nova.booting((Vue, router, store) => {
  Vue.component('index-eloquent-imagery', require('./components/NovaField/IndexField'))
  Vue.component('detail-eloquent-imagery', require('./components/NovaField/DetailField'))
  Vue.component('form-eloquent-imagery', require('./components/NovaField/FormField'))
})
