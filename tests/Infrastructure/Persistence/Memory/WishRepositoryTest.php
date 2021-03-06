<?php

namespace Wishlist\Tests\Infrastructure\Persistence\Memory;

use Money\Currency;
use Wishlist\Domain\Expense;
use Wishlist\Domain\Wish;
use Wishlist\Domain\WishId;
use Wishlist\Domain\WishName;
use Wishlist\Domain\WishRepositoryInterface;
use Wishlist\Infrastructure\Persistence\Memory\WishRepository;
use PHPUnit\Framework\TestCase;

class WishRepositoryTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorShouldAcceptWishesOnly()
    {
        new WishRepository([
            'foo',
            'bar',
        ]);
    }

    /**
     * @expectedException \Wishlist\Domain\Exception\WishNotFoundException
     */
    public function testGetShouldThrowOnNonExistentId()
    {
        $repository = new WishRepository();
        $repository->get(WishId::next());
    }

    public function testPutShouldSaveToInternalArray()
    {
        $repository = new WishRepository();
        $wishId = WishId::next();

        $repository->put(new Wish(
            $wishId,
            new WishName('Qux'),
            Expense::fromCurrencyAndScalars(
                new Currency('USD'),
                1000,
                20,
                400
            )
        ));

        static::assertEquals(1, $repository->count());
        static::assertSame($wishId, $repository->get($wishId)->getId());
    }

    public function testSliceShouldReturnAPortion()
    {
        $repository = new WishRepository();
        $wishes = $this->createWishesIndexedById($repository, 5);

        static::assertSame(
            array_slice($wishes, 1, 3, true),
            $repository->slice(1, 3)
        );
    }

    public function testHassersShouldSearchInInternalArray()
    {
        $repository = new WishRepository();
        $wishes = $this->createWishesIndexedByNumber($repository, 2);
        $anotherWish = $this->createWish();

        static::assertTrue($repository->contains($wishes[0]));
        static::assertTrue($repository->contains($wishes[1]));
        static::assertTrue($repository->containsId($wishes[0]->getId()));
        static::assertTrue($repository->containsId($wishes[1]->getId()));
        static::assertFalse($repository->contains($anotherWish));
        static::assertFalse($repository->containsId($anotherWish->getId()));
    }

    public function testNextWishIdMustAlwaysBeUnique()
    {
        $repository = new WishRepository();
        $wishIdOne = $repository->getNextWishId();
        $wishIdTwo = $repository->getNextWishId();

        static::assertNotSame($wishIdOne, $wishIdTwo);
        static::assertFalse($wishIdOne->equalTo($wishIdTwo));
    }

    private function createWishesIndexedByNumber(WishRepositoryInterface $repository, int $number)
    {
        $wishes = [];

        foreach (range(0, $number - 1) as $index) {
            $wishId = WishId::next();
            $wishes[$index] = $this->createWish($wishId);
            $repository->put($wishes[$index]);
        }

        return $wishes;
    }

    private function createWishesIndexedById(WishRepositoryInterface $repository, int $number)
    {
        $wishes = [];

        foreach (range(0, $number - 1) as $index) {
            $wishId = WishId::next();
            $wishes[$wishId->getId()] = $this->createWish($wishId);
            $repository->put($wishes[$wishId->getId()]);
        }

        return $wishes;
    }

    private function createWish(WishId $wishId = null): Wish
    {
        return new Wish(
            $wishId ?? WishId::next(),
            new WishName('Qux'),
            Expense::fromCurrencyAndScalars(
                new Currency('USD'),
                1000,
                20,
                400
            )
        );
    }
}
