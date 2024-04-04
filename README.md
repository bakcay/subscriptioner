### Subscriptioner 

Subscriptioner laravel 10 + sail ile çalışır , öncelikle lokalinizde docker, docker-compose yüklü ve ayakta olduğuna emin olun.


## Kurulum

Öncelikle projeyi klonlayın.

```bash
git clone git@github.com:bakcay/subscriptioner.git
```

Daha sonra projenin bulunduğu dizine gidin.

```bash
cd subscriptioner
```

Çalıştırmak için docker a ihtiyaç duyacaktır , docker kurulu değilse [docker](https://docs.docker.com/get-docker/) adresinden indirebilirsiniz.



.env dosyasını oluşturmak için aşağıdaki komutu çalıştırabilirsiniz.

```bash
cp .env.example .env
```

Oluşmuş .env dosyasındaki ZOTLO_* bilgilerini eksiksiz doldurun.

Gerekli dependency'leri yüklemek için aşağıdaki komutu çalıştırın.

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```

Sail aliası eklemek için aşağıdaki komutu çalıştırın.
```bash
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

Daha sonra aşağıdaki komutu çalıştırarak projeyi ayağa kaldırabilirsiniz.

```bash
sail up -d
```

Key generate etmeniz gerekli.

```bash
sail artisan key:generate
```

Ayrıca JWT secret key de generate etmeniz gerekli.

```bash
sail artisan jwt:secret
```

Veritabanı tablolarını da oluşturmak için aşağıdaki komutu çalıştırabilirsiniz.

```bash
sail artisan migrate:fresh --seed
```

TL:DR;

```bash
git clone git@github.com:bakcay/subscriptioner.gitcd subscriptioner
cp .env.example .env
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
sail up -d
sail artisan key:generate
sail artisan jwt:secret
sail artisan migrate:fresh --seed
```

Artık proje hazır. ve erişilebilir.

## Kullanım
[Buradaki](postman_collection.json) postman collection dosyasını import ederek kullanabilirsiniz. 

### Postman açıklamaları:

### Login
Bu endpoint, normal kullanıcıların sisteme giriş yapmalarını sağlar. Başarıyla giriş yapıldığında, otomatik olarak oluşturulan bir token döndürülür. Bu token, token gerektiren tüm endpointlerde kullanılmak üzere paylaşılır, böylece kullanıcıların kopyala yapıştır yapmasına gerek kalmaz.
### Admin Login
Raporlama işlemleri için tasarlanmıştır. Abonelik durumları gibi kritik verilerin raporlanması, sadece admin yetkisine sahip kullanıcılar tarafından yapılabilir. Uygulamanın kurulumu (seed) esnasında otomatik olarak bir adet admin kullanıcısı oluşturulur.
### Register
Bu endpoint, yeni kullanıcı hesapları oluşturmak için kullanılır. Kullanıcı kaydı için minimum olarak email ve şifre bilgileri gereklidir. İsteğe bağlı olarak ad, adres, şehir, bölge, ülke, telefon, vergi dairesi ve vergi numarası gibi ek bilgiler de girilebilir. Girilmeyen bilgiler, faker kütüphanesi tarafından otomatik olarak doldurulur. Email adresi benzersiz (unique) olmalıdır. Kullanıcı başarıyla oluşturulduktan sonra, token Postman'de login endpointiyle benzer şekilde global bir değişken olarak ayarlanır.
### Subscribe
Bu endpoint, tokeni girilen kullanıcının, henüz bir aboneliği yoksa, girilen kart bilgileriyle yeni bir abonelik oluşturmasını sağlar.
### Register&Subscribe
Bu endpoint, Register ve Subscribe işlemlerini tek bir adımda gerçekleştiren birleşik bir işlev sunar. İşlem sonucunda dönen token, Postman'de diğer endpointlerde kullanılmak üzere global bir değişken olarak ayarlanabilir.
### Cancel
Tokeni verilen kullanıcının mevcut aboneliğini pasifleştirir. Bu işlem, asenkron olarak bir kuyruğa eklenerek gerçekleştirilir. Real-time işlemi desteklemek için alternatif olarak kuyruğa eklenmeden de yapılabilecek bir işlem olsa da, bu seçenek yorum satırı olarak bırakılmıştır.
### Reactive
Tokeni verilen kullanıcının pasifleştirilmiş olan aboneliğini yeniden aktifleştirir.
### My Subscription
Bu endpoint, tokeni verilen kullanıcının, hem sistemdeki hem de uzaktan API üzerindeki abonelik bilgilerini çeker. Performansı artırmak için, event temelli flush edilen bir cache mekanizması ile desteklenir.
### My Card List
Tokeni verilen kullanıcının, uzaktan API üzerinde kayıtlı olan kartlarının listesini çeker. Performansı artırmak için, event temelli flush edilen bir cache mekanizması ile desteklenir.
### My Details
Bu endpoint, veritabanındaki kullanıcı bilgilerini döndürür. Performansı kısmen artırmak için, response zamanında cache'lenir.
### Report Single Day
Admin tokeni ile çalışan bu endpoint, seçilen tek bir gün için abonelik olaylarının özetini içerir.
### Report Day Range
Admin tokeni ile çalışan bu endpoint, en fazla 10 gün arasındaki abonelik olaylarının gruplanmış özetini sunar.
### Secure Hook
Bu endpoint, yerel (local) geliştirme ortamı olduğu için IP sınırlaması olmadan çalışır. API servisinden gelen hookların işlenerek abonelik durumlarının güncellenmesi gibi işlemlere dönüşmesini sağlar; örneğin, aboneliğin pasifleştirilmesi veya aktifleştirilmesi gibi.

### Açıklamalar
- Laravel 11 JWT Auth tam olarak uyumlu olmadığından 10 versiyonu kullanılmıştır.
- Email tekilliği request ve validationlar vasıtlasyla sağlamaktadır.
- Abonelik kullanıcılar için bir adet oluşturulabilir.
- Aboneliklerin pasif ve aktif durumları vardır.
- Abonelikler docker ayağa kalktığı andan itibaren dakika başı kontrol edilir. Durum , bitiş tarihi ve quantity bilgileri güncellenir.
- Migrationlar ve seederlar ile birlikte bir admin bir adet de normal kullanıcı oluşturulur. Postman collectionunda bunlar eklidir.
- Performans için mümkün olan yerlerde cache kullanılmıştır. Event bazlı invalidasyonlar yapılmıştır.
- Tabloların performassı için sorgulamalarda geçen kriterlerde index kullanılmıştır.
- Webhooklar için bir endpoint oluşturulmuştur. Bu endpoint yerelde çalıştığı için IP sınırlaması yoktur.

