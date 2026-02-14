import { ref, reactive, onMounted } from "vue";
import axios from "axios";

export function useAdmin() {
  const products = ref([]);
  const categories = ref([]);
  const currentCategoryId = ref(null);
  const viewMode = ref("list");
  const isEditing = ref(false);
  const fileInput = ref(null);

  const defaultForm = {
    id: null,
    name: "",
    price: "",
    category_name: "",
    description: "",
    image: null,
    previewUrl: null,
    has_cold_hot: false,
  };

  const form = reactive({ ...defaultForm });

  const fetchData = async () => {
    try {
      const [pRes, cRes] = await Promise.all([
        axios.get("/api/products"),
        axios.get("/api/menu"),
      ]);

      products.value = pRes.data;
      categories.value = cRes.data;

      if (categories.value.length) {
        currentCategoryId.value = categories.value[0].id;
      }
    } catch (err) {
      console.error("載入失敗:", err);
    }
  };

  const resetForm = () => {
    Object.assign(form, defaultForm);
    if (fileInput.value) fileInput.value.value = "";
  };

  const submitForm = async () => {
    const formData = new FormData();

    Object.keys(form).forEach((key) => {
      if (key !== "previewUrl" && key !== "id") {
        formData.append(key, form[key]);
      }
    });

    formData.set("has_cold_hot", form.has_cold_hot ? 1 : 0);

    try {
      if (isEditing.value) {
        formData.append("_method", "PUT");
        await axios.post(`/api/products/${form.id}`, formData);
        alert("修改成功！");
      } else {
        await axios.post("/api/products", formData);
        alert("新增成功！");
      }

      await fetchData();
      viewMode.value = "list";
      resetForm();
    } catch (err) {
      alert("儲存失敗");
      console.error(err);
    }
  };

  onMounted(fetchData);

  return {
    products,
    categories,
    currentCategoryId,
    viewMode,
    isEditing,
    form,
    fileInput,
    fetchData,
    submitForm,
    resetForm,
  };
}
