import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  vus: 5,
  duration: '30s',
  thresholds: {
    http_req_duration: ['p(95)<500'],
    http_req_failed: ['rate<0.01'],
  },
};

export default function () {
  const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000';
  const TOKEN = __ENV.SANCTUM_TOKEN || '';

  const url = `${BASE_URL}/api/articles`;

  const payload = JSON.stringify({
    title: `Test Article ${Date.now()}-${Math.floor(Math.random() * 1000)}`,
    body: 'This is the body of the test article created during k6 smoke testing.',
    status: 'published',
    cover_image: 'https://example.com/cover.jpg',
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${TOKEN}`,
    },
  };

  const res = http.post(url, payload, params);

  if (res.status !== 201 && res.status !== 200) {
    console.log(`Status: ${res.status} | Body: ${res.body}`);
  }

  check(res, {
    'is status 201 or 200': (r) => r.status === 201 || r.status === 200,
  });

  sleep(1);
}