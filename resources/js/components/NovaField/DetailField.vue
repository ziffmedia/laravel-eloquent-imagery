<template>
  <panel-item :field="field">
    <template slot="value">
      <p v-if="images.length == 0">
        —
      </p>

      <div v-if="images.length > 0" :class="`flex flex-wrap mb-2 laravel-eloquent-imagery-${this.resourceName}`">

        <div v-if="!field.isCollection" class="flex flex-wrap mb-2 laravel-eloquent-imagery-articles">
          <image-card-display v-if="images.length == 1" v-bind:image.sync="images[0]"></image-card-display>
        </div>

        <image-card-display
          v-if="field.isCollection"
          v-bind:image.sync="image"
          v-for="(image, index) in images"
          :key="index"
        >
        </image-card-display>

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

    computed: {
      images () {
        let images = (this.field.isCollection) ? this.field.value.images : (this.field.value ? [this.field.value] : [])

        return images.map((image, i) => {
          return {
            inputId: 'eloquent-imagery-' + this.field.name + '-' + i,
            previewUrl: image.previewUrl,
            thumbnailUrl: image.thumbnailUrl,
            metadata: Object.keys(image.metadata).map(key => ({'key': key, 'value': image.metadata[key]}))
          }
        })
      }
    }

  }
</script>
