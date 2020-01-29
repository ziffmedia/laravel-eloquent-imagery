<template>
  <panel-item :field="field">
    <template slot="value">
      <div :class="`flex flex-wrap mb-2 laravel-eloquent-imagery-${this.resourceName}`">
        <div v-for="image in images" class="pl-1 pr-1 border border-70 flex items-end m-1">
          <image-card-display v-bind:image.sync="image"></image-card-display>
        </div>
      </div>
    </template>
  </panel-item>
</template>

<script>
  import ImageCardDisplay from './ImageCardDisplay'

  export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],

    components: {
      ImageCardDisplay,
    },

    data: () => ({
      isCollection: false,
      images: [],
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
