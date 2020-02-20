<template>
  <div class="text-center">
    <img v-if="previewImageThumbnailUrl" :src="previewImageThumbnailUrl" class="w-16">
    <span v-if="!previewImageThumbnailUrl" class="text-80">no image</span>
  </div>
</template>

<script>
  export default {
    props: ['field'],

    computed: {
      previewImageThumbnailUrl () {
        let images = (this.field.isCollection) ? this.field.value.images : (this.field.value ? [this.field.value] : [])

        return (images.length > 0) ? images[0].thumbnailUrl : null
      }
    }
  }
</script>
