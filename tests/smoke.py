from http.cookiejar import CookieJar
from html.parser import HTMLParser
import json
from sys import argv
from urllib.error import HTTPError
from urllib.parse import urlencode, urljoin
from urllib.request import build_opener, HTTPCookieProcessor, Request
from xml.etree import ElementTree

base = (argv[1] if len(argv) > 1 else 'http://localhost/sloy/').rstrip('/') + '/'
opener = build_opener(HTTPCookieProcessor(CookieJar()))

class Page(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.links = []
        self.cards = []
        self.fields = {}
        self.feed(html)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'a' and 'product-card' in attrs.get('class', '').split():
            self.cards.append(attrs['href'])
        for key in ('href', 'src', 'action'):
            if key in attrs:
                self.links.append(attrs[key])
        if tag == 'input' and 'name' in attrs:
            self.fields[attrs['name']] = attrs.get('value', '')

def request(path, expected=200, form=None):
    data = urlencode(form).encode() if form is not None else None
    try:
        response = opener.open(urljoin(base, path), data=data, timeout=8)
    except HTTPError as error:
        response = error
    with response:
        assert response.status == expected, (path, response.status, expected)
        return response.geturl(), response.read()

def page(path, marker):
    _, body = request(path)
    html = body.decode('utf-8')
    assert marker in html, path
    result = Page(html)
    for link in result.links:
        assert urljoin(base, link).startswith(base), (path, link)
    return result

home = page('', 'Крупный план слоистого торца фанеры')
for asset in ('assets/style.css', 'assets/app.js', 'assets/images/hero.webp'):
    assert len(request(asset)[1]) > 100, asset
page('catalog/', 'Каталог материалов')
page('catalog/plitka/', 'Травертин')
page('catalog/?q=FK-12-1525', 'Фанера берёзовая ФК')
def plywood_cards(thicknesses, in_stock=False):
    filters = {'option[]': thicknesses, 'format[]': ['1525 × 1525 мм']}
    if in_stock:
        filters['stock'] = '1'
    url = urljoin(base, 'catalog/fanera/?' + urlencode(filters, doseq=True))
    full_page = Page(request(url)[1].decode('utf-8'))
    fragment = Request(url, headers={'X-Sloy-Fragment': 'catalog'})
    with opener.open(fragment, timeout=8) as response:
        ajax = json.load(response)
    assert Page(ajax['html']).cards == full_page.cards
    return {link.rstrip('/').split('/')[-1] for link in full_page.cards}

three = plywood_cards(['12 мм', '15 мм', '18 мм'])
assert {'fanera-fk-12-1525', 'fanera-shlif-15', 'fanera-fk-18-1525'} <= three
assert plywood_cards(['15 мм', '18 мм']) == three - plywood_cards(['12 мм'])
assert 'fanera-shlif-21' in plywood_cards(['21 мм'])
assert 'fanera-shlif-21' not in plywood_cards(['21 мм'], in_stock=True)

product = page('product/fanera-fk-12-1525/', 'FK-12-1525')
cart_url, cart = request('api.php', form={
    'action': 'add', 'csrf': product.fields['csrf'], 'id': product.fields['id'], 'qty': 1,
})
assert cart_url == urljoin(base, 'cart/'), cart_url
assert 'FK-12-1525' in cart.decode('utf-8')
page('checkout/', 'Подтвердить заказ')
page('admin/', 'Электронная почта')
request('api.php', expected=405)
request('api.php', expected=403, form={'action': 'add'})
request('nesushchestvuyushchaya-stranitsa/', expected=404)
for resource in ('app/bootstrap.php', 'database/sloy.sql', 'data/sessions/.gitkeep'):
    try:
        opener.open(urljoin(base, resource), timeout=8)
    except HTTPError as error:
        assert error.code in (403, 404), (resource, error.code)
    else:
        raise AssertionError(resource)
assert (base + 'sitemap.xml').encode() in request('robots.txt')[1]
sitemap = ElementTree.fromstring(request('sitemap.xml')[1])
locations = [node.text for node in sitemap.findall('.//{*}loc')]
assert base + 'catalog/' in locations
assert all(url.startswith(base) for url in locations)
print('OK — каталог, корзина, оформление, доступ, SEO и файлы оформления')
