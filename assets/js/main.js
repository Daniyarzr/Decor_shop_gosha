function addToCart(productId) {
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'ok') {
            alert('Товар добавлен в корзину!');
        }
    })
    .catch(err => console.error('Ошибка:', err));
}