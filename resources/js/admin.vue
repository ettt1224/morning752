<script setup>
import { ref, onMounted, reactive, computed } from "vue";

import axios from "axios";

// --- 狀態變數 ---

const products = ref([]);

const categories = ref([]);

const fileInput = ref(null);

const currentCategoryId = ref(null);

// 頁面模式：'list' (列表) | 'form' (編輯/新增表單)

const viewMode = ref("list");

const defaultForm = {
  id: null,

  name: "",

  price: "",

  category_name: "",

  description: "",

  image: null,

  previewUrl: null,

  has_cold_hot: false, // ❄️🔥 新增：冷熱選項開關
};

const form = reactive({ ...defaultForm });

const isEditing = ref(false);

// --- 1. 載入資料 ---

const fetchData = async () => {
  try {
    const pRes = await axios.get("/api/products");

    products.value = pRes.data;

    const cRes = await axios.get("/api/menu");

    categories.value = cRes.data;

    if (categories.value.length > 0) {
      currentCategoryId.value = categories.value[0].id;
    }
  } catch (error) {
    console.error("載入失敗:", error);
  }
};

onMounted(() => {
  fetchData();
});

// --- 2. 處理圖片 ---

const handleFileChange = (event) => {
  const file = event.target.files[0];

  if (file) {
    form.image = file;

    form.previewUrl = URL.createObjectURL(file);
  } else {
    form.image = null;

    form.previewUrl = null;
  }
};

// --- 3. 切換到表單模式 (新增或編輯) ---

const openEditor = (product = null) => {
  if (product) {
    // 編輯模式

    isEditing.value = true;

    form.id = product.id;

    form.name = product.name;

    form.price = product.price;

    form.description = product.description;

    // 轉換 boolean (確保是 true/false)

    form.has_cold_hot = Boolean(product.has_cold_hot);

    // 處理分類名稱

    const foundCat = categories.value.find((c) => c.id === product.category_id);

    form.category_name = foundCat ? foundCat.name : "";

    form.image = null;

    form.previewUrl = product.image;
  } else {
    // 新增模式

    resetForm();

    isEditing.value = false;
  }

  // 切換視圖

  viewMode.value = "form";

  window.scrollTo({ top: 0, behavior: "smooth" });
};

// --- 4. 返回列表 ---

const backToList = () => {
  viewMode.value = "list";

  resetForm();
};

// --- 5. 送出表單 ---

const submitForm = async () => {
  const formData = new FormData();

  formData.append("name", form.name);

  formData.append("price", form.price);

  formData.append("category_name", form.category_name);

  formData.append("description", form.description || "");

  // 傳送冷熱選項 (轉成 1 或 0)

  formData.append("has_cold_hot", form.has_cold_hot ? 1 : 0);

  if (form.image instanceof File) {
    formData.append("image", form.image);
  }

  try {
    if (isEditing.value) {
      formData.append("_method", "PUT");

      await axios.post(`/api/products/${form.id}`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });

      alert("修改成功！");
    } else {
      await axios.post("/api/products", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });

      alert("新增成功！");
    }

    fetchData();

    backToList(); // 成功後返回列表
  } catch (error) {
    console.error(error);

    alert("儲存失敗，請檢查欄位");
  }
};

// --- 6. 刪除 ---

const deleteProduct = async (id) => {
  if (!confirm("確定要刪除這個商品嗎？此動作無法復原！")) return;

  try {
    await axios.delete(`/api/products/${id}`);

    alert("刪除成功");

    fetchData();
  } catch (error) {
    alert("刪除失敗");
  }
};
const deleteCategory = async (category) => {
  // 1. 安全確認
  const msg = `確定要刪除分類「${category.name}」嗎？\n\n⚠️ 注意：如果該分類下還有商品，可能會被一併刪除或變成未分類(視後端設定而定)。`;
  if (!confirm(msg)) return;

  try {
    // 2. 呼叫後端 API 刪除
    await axios.delete(`/api/categories/${category.id}`);
    alert("分類已刪除！");

    // 3. 如果刪除的是當前選中的分類，稍微重置一下 ID 避免畫面卡在不存在的分類
    if (currentCategoryId.value === category.id) {
      currentCategoryId.value = null;
    }

    // 4. 重整資料 (重新載入分類與商品)
    fetchData();
  } catch (error) {
    console.error(error);
    alert("刪除失敗，請檢查 API 或網路狀態");
  }
};
const resetForm = () => {
  Object.assign(form, defaultForm);

  form.previewUrl = null;

  if (fileInput.value) fileInput.value.value = "";
};

const changeCategory = (id) => {
  currentCategoryId.value = id;
};
</script>

<template>
  <div class="manager-container">
    <header class="page-header">
      <h1>⚙️ 餐點管理</h1>

      <button v-if="viewMode === 'list'" @click="openEditor()" class="add-btn">
        + 新增商品
      </button>
    </header>

    <div v-if="viewMode === 'list'" class="list-view fade-in">
      <div class="category-nav">
        <button
          v-for="category in categories"
          :key="category.id"
          class="cat-btn"
          :class="{ active: currentCategoryId === category.id }"
          @click="changeCategory(category.id)"
        >
          {{ category.name }}

          <span class="del-icon" @click.stop="deleteCategory(category)"> &times; </span>
        </button>
      </div>

      <div class="card">
        <table class="product-table">
          <thead>
            <tr>
              <th width="80">圖片</th>

              <th>名稱</th>

              <th>分類</th>

              <th>選項</th>

              <th>價格</th>

              <th width="150">操作</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="p in products"
              :key="p.id"
              @click="openEditor(p)"
              class="clickable-row"
            >
              <td v-if="p.category.id == currentCategoryId">
                <img v-if="p.image" :src="p.image" class="thumb" />

                <span v-else class="no-img">無</span>
              </td>

              <td v-if="p.category.id == currentCategoryId" class="name-col">
                {{ p.name }}
              </td>

              <td v-if="p.category.id == currentCategoryId">
                <span class="badge category-badge">{{
                  p.category ? p.category.name : "未分類"
                }}</span>
              </td>

              <td v-if="p.category.id == currentCategoryId">
                <span v-if="p.has_cold_hot" class="badge cold-hot-badge">❄️🔥 冷/熱</span>
              </td>

              <td v-if="p.category.id == currentCategoryId" class="price">
                ${{ p.price }}
              </td>

              <td v-if="p.category.id == currentCategoryId" @click.stop>
                <button class="action-btn edit" @click="openEditor(p)">編輯</button>

                <button class="action-btn del" @click="deleteProduct(p.id)">刪除</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="products.length === 0" class="empty-state">
          目前沒有商品，點擊右上角新增
        </div>
      </div>
    </div>

    <div v-else class="form-view fade-in">
      <div class="card form-card">
        <div class="form-header">
          <h2>{{ isEditing ? "✏️ 編輯商品詳細資料" : "➕ 新增商品" }}</h2>

          <button @click="backToList" class="back-btn">← 返回列表</button>
        </div>

        <form @submit.prevent="submitForm" class="edit-form">
          <div class="form-grid">
            <div class="grid-col">
              <div class="form-group">
                <label>商品名稱</label>

                <input
                  v-model="form.name"
                  type="text"
                  required
                  placeholder="例如：珍珠奶茶"
                />
              </div>

              <div class="form-group">
                <label>分類</label>

                <input
                  v-model="form.category_name"
                  type="text"
                  list="category-list"
                  required
                  placeholder="輸入或選擇分類"
                />

                <datalist id="category-list">
                  <option
                    v-for="cat in categories"
                    :key="cat.id"
                    :value="cat.name"
                  ></option>
                </datalist>
              </div>

              <div class="form-group">
                <label>價格</label>

                <input v-model="form.price" type="number" required />
              </div>

              <div class="form-group checkbox-group">
                <input type="checkbox" id="cold_hot" v-model="form.has_cold_hot" />

                <label for="cold_hot">開放冷熱調整 (冰/熱)</label>
              </div>
            </div>

            <div class="grid-col">
              <div class="form-group">
                <label>商品圖片</label>

                <div class="image-upload-box">
                  <img
                    v-if="form.previewUrl"
                    :src="form.previewUrl"
                    class="preview-img"
                  />

                  <div v-else class="placeholder">預覽圖</div>

                  <input
                    type="file"
                    ref="fileInput"
                    @change="handleFileChange"
                    accept="image/*"
                  />
                </div>
              </div>

              <div class="form-group">
                <label>描述</label>

                <textarea
                  v-model="form.description"
                  rows="4"
                  placeholder="商品介紹..."
                ></textarea>
              </div>
            </div>
          </div>

          <div class="form-footer">
            <button type="submit" class="submit-btn">
              {{ isEditing ? "儲存變更" : "確認新增" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>


