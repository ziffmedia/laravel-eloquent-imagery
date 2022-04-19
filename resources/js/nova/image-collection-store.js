
export default {
  namespaced: true,

  state: () => ({
    fieldName: '',
    isReadonly: true,
    images: []
  }),

  mutations: {
    initialize (state, payload) {
      state.isReadonly = payload.isReadOnly ?? true
      state.fieldName = payload.fieldName

      state.images = payload.images.map((image, i) => {
        return { ...image, inputId: 'eloquent-imagery-' + payload.fieldName + '-' + (i + 1) }
      })
    },

    updateImages (state, images) {
      state.images = images
    }
  },

  actions: {
    addImageFromFile ({ state, commit }, payload) {
      const imageUrl = URL.createObjectURL(payload.file)

      // @todo handle metadata
      const metadata = {}

      // Object.keys(payload.metadata ?? [])
      // .map(key => ({ key, value: metadata[key] }))

      const image = {
        inputId: 'eloquent-imagery-' + state.fieldName + '-' + (state.images.length + 1),
        previewUrl: imageUrl,
        thumbnailUrl: imageUrl,
        metadata
      }

      const images = state.images
      images.push(image)

      commit('updateImages', images)

      return new Promise((resolve, reject) => {
        const reader = new FileReader()

        reader.addEventListener('load', () => {
          image.fileData = reader.result

          resolve(image)
        })

        reader.readAsDataURL(payload.file)
      })
    },

    removeImage ({ state, commit }, imageToRemove) {
      commit('updateImages', state.images.filter(image => image !== imageToRemove))
    }

    // updateImageMetadata ({ state, commit }, payload) {
    //   state.images.forEach((image) => {
    //
    //     if (payload.inputId && image.inputId && payload.inputId === image.inputId && payload.metadata) {
    //
    //       let newMetadata = Object.keys(payload.metadata).map(key => ({'key': key, 'value': payload.metadata[key]}))
    //       let oldMetadata = image.metadata;
    //       let metadata = {};
    //
    //       [oldMetadata, newMetadata].forEach((arr) => {
    //         arr.forEach((item) => {
    //           metadata[item['key']] = item['value']
    //         })
    //       });
    //
    //       image.metadata = Object.keys(metadata).map(key => ({'key': key, 'value': metadata[key]}))
    //     }
    //   });
    //
    //   commit('updateImages', images)
    // }
  },

  getters: {
    getImages: (state) => state.images,

    serialize: (state) => {
      return state.images.map(image => {
        return {
          metadata: image.metadata,
          path: image.path ?? null,
          ...(image.fileData ? { fileData: image.fileData } : {})
        }
      })
    }
  }
}
