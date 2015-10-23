describe('Nette.validators', function() {

	it('equal', function() {
		expect(Nette.validators.equal(null, '', '')).toBe(true);
		expect(Nette.validators.equal(null, '', 'a')).toBe(false);
		expect(Nette.validators.equal(null, 'a', 'a')).toBe(true);
		expect(Nette.validators.equal(null, 0, '0')).toBe(true);
		expect(Nette.validators.equal(null, 0, '')).toBe(false);
		expect(Nette.validators.equal(null, null, '')).toBe(true);
		expect(Nette.validators.equal(null, false, '')).toBe(true);
		expect(Nette.validators.equal(null, false, 0)).toBe(false);
		expect(Nette.validators.equal(null, true, '1')).toBe(true);
		expect(Nette.validators.equal(null, 'a', ['a'])).toBe(true);
		expect(Nette.validators.equal(null, 'a', ['b'])).toBe(false);
		expect(Nette.validators.equal(null, 'a', [])).toBe(true);
		expect(Nette.validators.equal(null, ['a'], 'a')).toBe(true);
		expect(Nette.validators.equal(null, ['a'], 'b')).toBe(false);
		expect(Nette.validators.equal(null, [], 'b')).toBe(false);
		expect(Nette.validators.equal(null, ['a'], ['a'])).toBe(true);
		expect(Nette.validators.equal(null, ['a'], ['b'])).toBe(false);
		expect(Nette.validators.equal(null, ['a'], [])).toBe(true);
	});


	it('email', function() {
		expect(Nette.validators.email(null, null, '')).toBe(false);
		expect(Nette.validators.email(null, null, 'hello')).toBe(false);
		expect(Nette.validators.email(null, null, 'hello@world.cz')).toBe(true);
		expect(Nette.validators.email(null, null, 'hello@localhost')).toBe(false);
		expect(Nette.validators.email(null, null, 'hello@127.0.0.1')).toBe(false);
		expect(Nette.validators.email(null, null, 'hello@localhost.a0')).toBe(false);
		expect(Nette.validators.email(null, null, 'hello@localhost.0a')).toBe(false);
		expect(Nette.validators.email(null, null, 'hello@l.org')).toBe(true);
		expect(Nette.validators.email(null, null, 'hello@1.org')).toBe(true);
		expect(Nette.validators.email(null, null, 'jean.françois@lyotard.fr')).toBe(false);
		expect(Nette.validators.email(null, null, 'jerzy@kosiński.pl')).toBe(true);
		expect(Nette.validators.email(null, null, 'péter@esterházy.hu')).toBe(false);
		expect(Nette.validators.email(null, null, 'hello@1.c0m')).toBe(true);
		expect(Nette.validators.email(null, null, 'hello@1.c')).toBe(true);
	});


	it('url', function() {
		var v = {value: null};
		expect(Nette.validators.url(null, null, '', v)).toBe(false);
		expect(Nette.validators.url(null, null, 'hello', v)).toBe(true);
		expect(v.value === 'http://hello').toBe(true);
		expect(Nette.validators.url(null, null, 'nette.org', v)).toBe(true);
		expect(v.value === 'http://nette.org').toBe(true);
		expect(Nette.validators.url(null, null, 'http://nette.org0', v)).toBe(false);
		expect(Nette.validators.url(null, null, 'http://nette.0org', v)).toBe(false);
		expect(Nette.validators.url(null, null, 'http://_nette.org', v)).toBe(false);
		expect(Nette.validators.url(null, null, 'http://www._nette.org', v)).toBe(false);
		expect(Nette.validators.url(null, null, 'http://www.ne_tte.org', v)).toBe(false);
		expect(Nette.validators.url(null, null, 'http://1.org', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'http://l.org', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'http://localhost', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'http://127.0.0.1', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'http://[::1]', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'http://[2001:0db8:0000:0000:0000:0000:1428:57AB]', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'http://nette.org/path', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'http://nette.org:8080/path', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'https://www.nette.org/path', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'https://example.c0m', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'https://example.l', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'http://one_two.example.com', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'http://_.example.com', v)).toBe(true);
		expect(Nette.validators.url(null, null, 'http://_e_.example.com', v)).toBe(true);
	});

});
