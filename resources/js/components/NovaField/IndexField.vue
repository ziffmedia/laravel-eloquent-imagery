<template>
  <div class="text-center">
    <img v-if="previewImageThumbnailUrl" :src="previewImageThumbnailUrl" class="w-16">
    <span v-if="!previewImageThumbnailUrl" class="text-80">—</span>
  </div>
</template>

<script>
  export default {
    props: ['field'],

    computed: {
      previewImageThumbnailUrl () {
        if (this.field.isCollection) {
          return this.field.value.images.length !== 0 ? this.field.images[0].thumbnailUrl : null
        }

        return this.field.value.hasOwnProperty('thumbnailUrl') ? this.field.value.thumbnailUrl : null
      }
    }
  }
</script>
