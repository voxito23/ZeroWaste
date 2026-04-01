// Configuración de Firebase para ZeroWaste
// Se carga como módulo ES6 desde los templates que lo requieran

import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import { getAuth, GoogleAuthProvider, signInWithPopup } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-auth.js";

const firebaseConfig = {
    apiKey: "AIzaSyCNxoLg8OetTvbYFTdw1obSP9L2NtLVxTU",
    authDomain: "zerowaste-57c55.firebaseapp.com",
    projectId: "zerowaste-57c55",
    storageBucket: "zerowaste-57c55.firebasestorage.app",
    messagingSenderId: "95524501274",
    appId: "1:95524501274:web:38fafb7316844cdd6f1ea3",
    measurementId: "G-MKY08QXC2M"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const googleProvider = new GoogleAuthProvider();

export { auth, googleProvider, signInWithPopup };
