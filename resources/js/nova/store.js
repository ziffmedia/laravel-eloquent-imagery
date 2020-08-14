
export default {
  namespaced: true,

  state: {
    fieldName: '',
    images: [],
    isCollection: false
  },

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

    updateImageMetaData ({ state, commit }, payload) {
      let images = state.images;

      images.forEach((image) => {
        if (payload.inputId && image.inputId && payload.inputId === image.inputId && payload.metaData) {
          let newMetaData = Object.keys(payload.metaData).map(key => ({'key': key, 'value': payload.metaData[key]}))
          let oldMetaData = image.metadata;
          let metaData = {};

          [oldMetaData, newMetaData].forEach((arr) => {
            arr.forEach((item) => {
              metaData[item['key']] = item['value']
            })
          });

          image.metadata = Object.keys(metaData).map(key => ({'key': key, 'value': metaData[key]}))
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
