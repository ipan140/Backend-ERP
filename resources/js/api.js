// // src/api.js
// import axios from "axios";

// // ✅ Sesuaikan baseURL dengan backend kamu
// const api = axios.create({
//   baseURL: import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api",
//   headers: {
//     "Content-Type": "application/json",
//     Accept: "application/json",
//   },
//   withCredentials: true, // ✅ penting kalau pakai Sanctum (cookie-based)
// });

// // ✅ Tambah token otomatis kalau pakai JWT
// api.interceptors.request.use(
//   (config) => {
//     const token = localStorage.getItem("token"); // simpan token setelah login
//     if (token) {
//       config.headers.Authorization = `Bearer ${token}`;
//     }
//     return config;
//   },
//   (error) => Promise.reject(error)
// );

// export default api;
