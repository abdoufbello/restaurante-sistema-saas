#!/usr/bin/env node

/**
 * 🔑 Gerador de Chaves de Segurança para Deploy
 * 
 * Este script gera todas as chaves necessárias para o deploy na Vercel
 * Execute: node generate_keys.js
 */

const crypto = require('crypto');

console.log('🔑 GERADOR DE CHAVES DE SEGURANÇA\n');
console.log('=' .repeat(50));

// Encryption Key (32 caracteres)
const encryptionKey = crypto.randomBytes(16).toString('hex');
console.log('🔐 ENCRYPTION KEY (32 chars):');
console.log(`encryption.key = ${encryptionKey}`);
console.log('');

// JWT Secret (64 caracteres)
const jwtSecret = crypto.randomBytes(32).toString('hex');
console.log('🎫 JWT SECRET (64 chars):');
console.log(`jwt.secret = ${jwtSecret}`);
console.log('');

// Webhook Token para WhatsApp (32 caracteres)
const webhookToken = crypto.randomBytes(16).toString('hex');
console.log('📱 WHATSAPP WEBHOOK TOKEN:');
console.log(`whatsapp.webhookToken = ${webhookToken}`);
console.log('');

// Mercado Pago Webhook Secret
const mpWebhookSecret = crypto.randomBytes(24).toString('hex');
console.log('💳 MERCADO PAGO WEBHOOK SECRET:');
console.log(`mercadopago.webhookSecret = ${mpWebhookSecret}`);
console.log('');

// Stripe Webhook Secret
const stripeWebhookSecret = crypto.randomBytes(24).toString('hex');
console.log('💰 STRIPE WEBHOOK SECRET:');
console.log(`stripe.webhookSecret = ${stripeWebhookSecret}`);
console.log('');

console.log('=' .repeat(50));
console.log('✅ CHAVES GERADAS COM SUCESSO!');
console.log('');
console.log('📋 PRÓXIMOS PASSOS:');
console.log('1. Copie as chaves acima');
console.log('2. Cole na Vercel (Settings > Environment Variables)');
console.log('3. NUNCA compartilhe essas chaves!');
console.log('4. Use chaves diferentes para desenvolvimento/produção');
console.log('');
console.log('🔒 IMPORTANTE: Guarde essas chaves em local seguro!');