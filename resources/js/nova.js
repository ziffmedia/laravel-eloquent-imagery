Nova.booting((Vue, router, store) => {
  Vue.component('index-eloquent-imagery', require('./components/NovaField/IndexField').default)
  Vue.component('detail-eloquent-imagery', require('./components/NovaField/DetailField').default)
  Vue.component('form-eloquent-imagery', require('./components/NovaField/FormField').default)
})
