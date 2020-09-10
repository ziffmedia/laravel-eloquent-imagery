
export default {
  namespaced: true,

  state: () => ({
    fieldName: '',
    images: [],
    isCollection: false
  }),

  mutations: {
    initialize(state, payload) {
      state.fieldName = payload.fieldName
      state.images = payload.images
      state.isCollection = payload.isCollection
    },

    updateImages(state, images) {
      state.images = images
    }
  },

  actions: {
    addImageFromFile ({ state, commit }, payload) {
      let file = payload.file

      let imageUrl = URL.createObjectURL(file)

      let metadata = payload.hasOwnProperty('metadata') ? payload.metadata : []

      let image = {
        inputId: 'eloquent-imagery-' + state.fieldName + '-' + (state.images.length + 1),
        previewUrl: imageUrl,
        thumbnailUrl: imageUrl,
        metadata: Object.keys(metadata).map(key => ({'key': key, 'value': metadata[key]})),
      }

      let images = state.images
      images.push(image)

      commit('updateImages', images)

      return new Promise((resolve, reject) => {
        let reader = new FileReader()

        reader.addEventListener('load', () => {
          image.fileData = reader.result

          resolve(image)
        })

        reader.readAsDataURL(file)
      })
    },

    removeImage ({ state, commit }, imageToRemove) {
      commit('updateImages', state.images.filter(image => image !== imageToRemove))
    },

    updateImageMetadata ({ state, commit }, payload) {
      let images = state.images;

      images.forEach((image) => {
        if (payload.inputId && image.inputId && payload.inputId === image.inputId && payload.metadata) {
          let newMetadata = Object.keys(payload.metadata).map(key => ({'key': key, 'value': payload.metadata[key]}))
          let oldMetadata = image.metadata;
          let metadata = {};

          [oldMetadata, newMetadata].forEach((arr) => {
            arr.forEach((item) => {
              metadata[item['key']] = item['value']
            })
          });

          image.metadata = Object.keys(metadata).map(key => ({'key': key, 'value': metadata[key]}))
        }
      });

      commit('updateImages', images)
    }
  },

  getters: {
    getImages: (state) => state.images,
    getIsCollection: (state) => state.isCollection
  }
}
