<template>
  <panel-item :field="field">
    <template slot="value">
      <div class="bg-white shadow-lg rounded-lg">
        <div class="flex px-6 py-4" v-for="image in images">
          <div>
            <img style="max-height: 80px" class="block mx-auto mb-4 sm:mb-0 sm:mr-4 sm:ml-0" :src="image.previewUrl">
          </div>
          <div class="w-full">
            <span class="text-sm leading-tight text-grey-dark">
                Image Metadata:
            </span>
            <div class="flex -mx-3 px-3" v-for="(metadata, index) in image.metadata">
              <div class="w-1/3 text-xs text-right mr-3">
                {{ image.metadata[index].key }}:
              </div>
              <div class="w-full text-xs">
                {{ image.metadata[index].value }}
              </div>
            </div>

          </div>
        </div>
      </div>
    </template>
  </panel-item>
</template>

<script>
  export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],

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
          metadata: Object.keys(image.metadata).map(key => ({'key': key, 'value': image.metadata[key]}))
        }
      })
    }
  }
</script>
