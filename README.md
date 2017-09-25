# PHP-Test-Sharding

Реализация распределённого хранения информации о пользователях (тестовое задание).

## Используемые таблицы баз данных с примерным содержимым:

### Мастер-сервер:

#### dsns - источники данных
<table>
  <tr>
    <th>id</th><th>driver</th><th>host</th><th>port</th><th>username</th><th>pasword</th><th>dbname</th>
  </tr>
  <tr>
    <td>1</td><td>mysql</td><td>&hellip;</td><td>&hellip;</td><td>&hellip;</td><td>&hellip;</td><td>testusers1</td>
  </tr>
  <tr>
    <td>2</td><td>mysql</td><td>&hellip;</td><td>&hellip;</td><td>&hellip;</td><td>&hellip;</td><td>testusers2</td>
  </tr>
</table>

#### uids - размещение записей о пользователях по источникам
<table>
  <tr>
    <th>uid</th><th>dsnid</th>
  </tr>
  <tr>
    <td>1</td><td>1</td>
  </tr>
  <tr>
    <td>2</td><td>2</td>
  </tr>
  <tr>
    <td>3</td><td>1</td>
  </tr>
  <tr>
    <td>4</td><td>1</td>
  </tr>
  <tr>
    <td>5</td><td>2</td>
  </tr>
</table>

### Слейв-серверы:

#### testusers1.users - сведения о пользователях (слейв 1 / DSN1)
<table>
  <tr>
    <th>id</th><th>name</th><th>lastname</th><th>dob</th>
  </tr>
  <tr>
    <td>1</td><td>John</td><td>Tucker</td><td>1985-12-25</td>
  </tr>
  <tr>
    <td>3</td><td>Jane</td><td>Mighty</td><td>1982-07-01</td>
  </tr>
  <tr>
    <td>4</td><td>Kyel</td><td>Bayer</td><td>1988-09-11</td>
  </tr>
</table>

#### testusers2.users - сведения о пользователях (слейв 2 / DSN2)
<table>
  <tr>
    <th>id</th><th>name</th><th>lastname</th><th>dob</th>
  </tr>
  <tr>
    <td>2</td><td>Ann</td><td>Donovan</td><td>1985-11-20</td>
  </tr>
  <tr>
    <td>5</td><td>Miles</td><td>Leen</td><td>1984-04-14</td>
  </tr>
</table>
