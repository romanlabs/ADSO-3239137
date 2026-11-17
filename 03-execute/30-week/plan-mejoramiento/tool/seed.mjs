// Demo data seeder. It only talks to the HTTP API under /api/v1, never to the database (plan D-16).
//
// Two phases, because the API decides every instant with its own clock (ADR-005):
//   node seed.mjs past     -> API started with CLOCK_OFFSET_DAYS=-20: catalog, students and the loans that end overdue
//   node seed.mjs present  -> API started with CLOCK_OFFSET_DAYS=0: the loans of today
// seed.sh runs both phases and restarts the API in between.
//
// Totals: 40 laptops, 10 students, 15 loans, 3 of them overdue.

const API_URL = (process.env.API_URL ?? 'http://localhost:8000').replace(/\/$/, '') + '/api/v1';
const ADMIN_USERNAME = process.env.ADMIN_USERNAME;
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD;

export const LAPTOP_COUNT = 40;
export const STUDENT_COUNT = 10;
export const LOAN_COUNT = 15;
export const OVERDUE_COUNT = 3;

const CATEGORY = {
  basic: '11111111-1111-4111-8111-111111111111',
  design: '22222222-2222-4222-8222-222222222222',
  programming: '33333333-3333-4333-8333-333333333333',
  tablets: '44444444-4444-4444-8444-444444444444',
  accessories: '55555555-5555-4555-8555-555555555555',
};

// [name, brand, model_code, category, total_units]
export const LAPTOPS = [
  ['IdeaPad 3 15', 'Lenovo', 'IP3-15-ALC6', CATEGORY.basic, 6],
  ['IdeaPad 1 14', 'Lenovo', 'IP1-14-AMN7', CATEGORY.basic, 5],
  ['Inspiron 15 3520', 'Dell', 'INS-3520', CATEGORY.basic, 5],
  ['Inspiron 14 5430', 'Dell', 'INS-5430', CATEGORY.basic, 4],
  ['HP 15 fd0000', 'HP', 'HP15-FD0', CATEGORY.basic, 6],
  ['HP 14 ep0000', 'HP', 'HP14-EP0', CATEGORY.basic, 4],
  ['Aspire 3 A315', 'Acer', 'ASP3-A315', CATEGORY.basic, 5],
  ['Aspire 5 A515', 'Acer', 'ASP5-A515', CATEGORY.basic, 3],
  ['Vivobook 15 X1504', 'ASUS', 'VB15-X1504', CATEGORY.basic, 5],
  ['Chromebook Plus 514', 'Acer', 'CBP-514', CATEGORY.basic, 4],
  ['MacBook Pro 14 M3', 'Apple', 'MBP14-M3', CATEGORY.design, 3],
  ['MacBook Pro 16 M3 Pro', 'Apple', 'MBP16-M3P', CATEGORY.design, 2],
  ['MacBook Air 15 M2', 'Apple', 'MBA15-M2', CATEGORY.design, 3],
  ['ZBook Firefly 14 G10', 'HP', 'ZBF14-G10', CATEGORY.design, 2],
  ['ZBook Power G10', 'HP', 'ZBP-G10', CATEGORY.design, 2],
  ['ProArt Studiobook 16', 'ASUS', 'PASB16-H7604', CATEGORY.design, 2],
  ['Creator Z16', 'MSI', 'CRZ16-A12U', CATEGORY.design, 1],
  ['XPS 15 9530', 'Dell', 'XPS15-9530', CATEGORY.design, 3],
  ['Yoga Pro 9i', 'Lenovo', 'YP9I-G8', CATEGORY.design, 2],
  ['Surface Laptop Studio 2', 'Microsoft', 'SLS2-I7', CATEGORY.design, 2],
  ['ThinkPad E14 Gen 5', 'Lenovo', 'TP-E14-G5', CATEGORY.programming, 6],
  ['ThinkPad T14 Gen 4', 'Lenovo', 'TP-T14-G4', CATEGORY.programming, 4],
  ['ThinkPad X1 Carbon Gen 11', 'Lenovo', 'TP-X1C-G11', CATEGORY.programming, 2],
  ['Latitude 3440', 'Dell', 'LAT-3440', CATEGORY.programming, 5],
  ['Latitude 5440', 'Dell', 'LAT-5440', CATEGORY.programming, 4],
  ['XPS 13 9340', 'Dell', 'XPS13-9340', CATEGORY.programming, 2],
  ['EliteBook 840 G10', 'HP', 'EB840-G10', CATEGORY.programming, 3],
  ['ProBook 450 G10', 'HP', 'PB450-G10', CATEGORY.programming, 4],
  ['MacBook Air 13 M2', 'Apple', 'MBA13-M2', CATEGORY.programming, 4],
  ['Zenbook 14 OLED', 'ASUS', 'ZB14-UX3405', CATEGORY.programming, 3],
  ['iPad 10a generación', 'Apple', 'IPAD-10', CATEGORY.tablets, 6],
  ['iPad Air M2', 'Apple', 'IPADAIR-M2', CATEGORY.tablets, 3],
  ['Galaxy Tab S9 FE', 'Samsung', 'GTS9-FE', CATEGORY.tablets, 5],
  ['Galaxy Tab A9+', 'Samsung', 'GTA9-PLUS', CATEGORY.tablets, 6],
  ['Tab P12', 'Lenovo', 'TABP12-TB370', CATEGORY.tablets, 4],
  ['Surface Pro 9', 'Microsoft', 'SP9-I5', CATEGORY.tablets, 2],
  ['Cargador USB-C 65 W', 'Lenovo', 'CHG-USBC-65', CATEGORY.accessories, 10],
  ['Mouse inalámbrico M185', 'Logitech', 'MSE-M185', CATEGORY.accessories, 12],
  ['Base USB-C 7 en 1', 'Anker', 'HUB-A8346', CATEGORY.accessories, 8],
  ['Audífonos H390', 'Logitech', 'HDS-H390', CATEGORY.accessories, 8],
];

// [full_name, document_number]
export const STUDENTS = [
  ['Laura Gómez Ríos', '1001234567'],
  ['Andrés Felipe Rojas', '1009876543'],
  ['Valentina Castro Díaz', '1012345678'],
  ['Santiago Herrera Mejía', '1023456789'],
  ['Camila Torres Vargas', '1034567890'],
  ['Juan David Morales', '1045678901'],
  ['Daniela Ospina Cárdenas', '1056789012'],
  ['Mateo Ramírez Salazar', '1067890123'],
  ['Isabella Navarro León', '1078901234'],
  ['Sebastián Quintero Gil', '1089012345'],
];

class ApiError extends Error {
  constructor(method, path, status, body) {
    super(`${method} ${path} -> ${status} ${body?.code ?? ''} ${body?.message ?? ''}`.trim());
    this.status = status;
    this.code = body?.code;
  }
}

async function request(token, method, path, body) {
  const response = await fetch(API_URL + path, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body === undefined ? {} : { 'Content-Type': 'application/json' }),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  const text = await response.text();
  const json = text === '' ? null : JSON.parse(text);
  if (!response.ok) {
    throw new ApiError(method, path, response.status, json);
  }
  return json;
}

async function login() {
  if (!ADMIN_USERNAME || !ADMIN_PASSWORD) {
    throw new Error('Define ADMIN_USERNAME y ADMIN_PASSWORD (los mismos de infra/.env).');
  }
  const { access_token: token } = await request(null, 'POST', '/auth/login', {
    username: ADMIN_USERNAME,
    password: ADMIN_PASSWORD,
  });
  return token;
}

async function allPages(token, path) {
  const items = [];
  for (let page = 1; ; page += 1) {
    const separator = path.includes('?') ? '&' : '?';
    const result = await request(token, 'GET', `${path}${separator}page=${page}&size=50`);
    items.push(...result.items);
    if (items.length >= result.total || result.items.length === 0) {
      return items;
    }
  }
}

async function lend(token, studentId, laptopId) {
  return request(token, 'POST', '/loans', { student_id: studentId, items: [{ laptop_id: laptopId }] });
}

async function giveBack(token, loan) {
  return request(token, 'POST', `/loans/${loan.id}/items/${loan.items[0].id}/return`);
}

async function seedPast(token) {
  const existing = await request(token, 'GET', '/laptops?size=1');
  if (existing.total > 0) {
    throw new Error('La base ya tiene portátiles: el sembrador solo corre sobre una base recién creada.');
  }

  const laptops = [];
  for (const [name, brand, modelCode, categoryId, totalUnits] of LAPTOPS) {
    laptops.push(await request(token, 'POST', '/laptops', {
      name, brand, model_code: modelCode, category_id: categoryId, total_units: totalUnits,
    }));
  }

  const students = [];
  for (const [fullName, documentNumber] of STUDENTS) {
    students.push(await request(token, 'POST', '/students', { full_name: fullName, document_number: documentNumber }));
  }

  // 3 loans that will be overdue once the clock is back to today (students 1 to 3).
  for (let i = 0; i < OVERDUE_COUNT; i += 1) {
    await lend(token, students[i].id, laptops[20 + i].id);
  }
  // 3 loans already returned in the past (students 4 to 6).
  for (let i = 3; i < 6; i += 1) {
    await giveBack(token, await lend(token, students[i].id, laptops[i].id));
  }

  console.log(`Fase pasada: ${laptops.length} portátiles, ${students.length} estudiantes, 6 préstamos (3 quedarán vencidos).`);
}

async function seedPresent(token) {
  const students = await allPages(token, '/students');
  const laptops = await allPages(token, '/laptops');
  if (students.length !== STUDENT_COUNT || laptops.length !== LAPTOP_COUNT) {
    throw new Error('Primero corre la fase "past" sobre una base recién creada.');
  }
  const byDocument = new Map(students.map((student) => [student.document_number, student]));
  const ordered = STUDENTS.map(([, documentNumber]) => byDocument.get(documentNumber));
  const byCode = new Map(laptops.map((laptop) => [laptop.model_code, laptop]));
  const laptopAt = (index) => byCode.get(LAPTOPS[index][2]).id;

  // 2 loans returned today (students 4 and 5), then 7 open loans (students 4 to 10).
  for (let i = 3; i < 5; i += 1) {
    await giveBack(token, await lend(token, ordered[i].id, laptopAt(10 + i)));
  }
  for (let i = 3; i < STUDENT_COUNT; i += 1) {
    await lend(token, ordered[i].id, laptopAt(20 + i));
  }

  const loans = await request(token, 'GET', '/loans?size=50');
  const overdue = await request(token, 'GET', '/loans/overdue');
  const today = new Date().toISOString().slice(0, 10);
  const monthAgo = new Date(Date.now() - 40 * 86400000).toISOString().slice(0, 10);
  const report = await request(token, 'GET', `/reports/most-requested?from=${monthAgo}&to=${today}`);

  console.log(`Préstamos: ${loans.total} (se esperan ${LOAN_COUNT}).`);
  console.log(`Vencidos: ${overdue.items.length} (se esperan ${OVERDUE_COUNT}).`);
  console.log(`Filas del reporte de más solicitados: ${report.rows.length}.`);

  if (loans.total !== LOAN_COUNT || overdue.items.length !== OVERDUE_COUNT || report.rows.length === 0) {
    throw new Error('Los totales no coinciden con lo esperado.');
  }
}

async function main() {
  const phase = process.argv[2];
  if (phase !== 'past' && phase !== 'present') {
    throw new Error('Uso: node seed.mjs past | present  (o ./seed.sh para las dos fases).');
  }
  const token = await login();
  await (phase === 'past' ? seedPast(token) : seedPresent(token));
}

main().catch((error) => {
  console.error(`Error del sembrador: ${error.message}`);
  process.exitCode = 1;
});
