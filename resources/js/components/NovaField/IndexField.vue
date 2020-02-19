<template>
    <img :src="images[0]['thumbnailUrl']" class="w-16">
</template>

<script>
  export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],

    data: () => ({
      isCollection: false,
      images: []
    }),

    mounted () {
      let images = (this.field.isCollection) ? this.field.value.images : (this.field.value ? [this.field.value] : [])

      this.images = images.map((image, i) => {

        return {
          inputId: 'eloquent-imagery-' + this.field.name + '-' + i,
          previewUrl: image.previewUrl,
          thumbnailUrl: image.thumbnailUrl,
          metadata: Object.keys(image.metadata).map(key => ({'key': key, 'value': image.metadata[key]}))
        }
      })

    }
  }
</script>
